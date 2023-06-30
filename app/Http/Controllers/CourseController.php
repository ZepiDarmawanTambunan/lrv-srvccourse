<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Mentor;
use App\Models\MyCourse;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $courses = Course::query();

        $q = $request->query('q');
        $status = $request->query('status');

        $courses->when($q, function($query) use ($q){
            return $query->whereRaw("name LIKE '%".strtolower($q)."$'");
        });

        $courses->when($status, function($query) use ($status){
            return $query->where("status", "=", $status);
        });

        return response()->json([
            'status' => 'success',
            'data' => $courses->paginate(10)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string',
            'certificate' => 'required|boolean',
            'thumbnail' => 'string|url',
            'type' => 'required|in:free,premium',
            'status' => 'required|in:draft,published',
            'price' => 'integer',
            'level' => 'required|in:all-level,beginner,intermediate,advance',
            'mentor_id' => 'required|integer',
            // 'mentor_id' => 'required|integer|exists:mentors,id',
            'description' => 'string'
        ];

        $data = $request->all();

        $validator = Validator::make($data, $rules);
        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        $mentorId = $request->input('mentor_id');
        $mentor = Mentor::find($mentorId);
        if(!$mentor){
            return response()->json([
                'status' => 'error',
                'message' => 'mentor not found'
            ], 404);
        }

        $course = Course::create($data);
        return response()->json([
            'status' => 'success',
            'data' => $course
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $course = Course::with('chapters.lessons')
        ->with('mentor')
        ->with('images')
        ->find($id);

        if(!$course){
            return response()->json([
                'status' => 'error',
                'message' => 'course not found'
            ], 404);
        }

        // Review::with('users')->get();
        $reviews = Review::where('course_id', '=', $id)->get()->toArray();
        if(count($reviews)>0){
            $userIds = array_column($reviews, 'user_id'); //ambil column ['id' => 1, 'id' => 2]
            $users = getUserByIds($userIds); // [status: 'success', data:[{id:1, name:'user1'}, {id:2, name:'user2'}]]
            if($users['status'] == 'error'){
                $reviews = [];
            }else{
                foreach ($reviews as $key => $review) {
                    $userIndex = array_search($review['user_id'], array_column($users['data'], 'id')); //array_search(1, ['id' => 1, 'id' => 2]) -> 0 (index)
                    $reviews[$key]['users'] = $users['data'][$userIndex]; // [id => 1, rating => 3, users: [id => 1, name: 'user1']]
                }
            }
        }

        $totalStudent = MyCourse::where('course_id', '=', $id)->count();
        $totalVideos = Chapter::where('course_id', '=', $id)->withCount('lessons')->get()->toArray();
        $finalTotalVideos = array_sum(array_column($totalVideos, 'lessons_count'));

        $course['reviews'] = $reviews;
        $course['total_videos'] = $finalTotalVideos;
        $course['total_student'] = $totalStudent;

        return response()->json([
            'status' => 'success',
            'data' => $course
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $rules = [
            'name' => 'string',
            'certificate' => 'boolean',
            'thumbnail' => 'string|url',
            'type' => 'in:free,premium',
            'status' => 'in:draft,published',
            'price' => 'integer',
            'level' => 'in:all-level,beginner,intermediate,advance',
            'mentor_id' => 'integer',
            'description' => 'string'
        ];

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        $course = Course::find($id);
        if(!$course){
            return response()->json([
                'status' => 'error',
                'message' => 'course not found'
            ], 404);
        }

        $mentorId = $request->input('mentor_id');
        if($mentorId){
            $mentor = Mentor::find($mentorId);
            if(!$mentor){
                return response()->json([
                    'status' => 'error',
                    'message' => 'mentor not found'
                ], 404);
            }
        }

        $course->fill($data);
        $course->save();

        return response()->json([
            'status' => 'success',
            'data' => $course
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $course = Course::find($id);

        if(!$course){
            return response()->json([
                'status' => 'error',
                'message' => 'course not found'
            ]);
        }

        $course->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'deleted success'
        ]);
    }
}
