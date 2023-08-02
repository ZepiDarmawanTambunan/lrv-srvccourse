<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\MyCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MyCourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $myCourses = MyCourse::query()->with('course');
        $userId = $request->query('user_id');
        $myCourses->when($userId, function($query) use ($userId){
            return $query->where('user_id', '=', $userId);
        });

        return response()->json([
            'status' => 'success',
            'data' => $myCourses->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'course_id' => 'required|integer',
            'user_id' => 'required|integer'
        ];

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        $courseId = $request->input('course_id');
        $course = Course::find($courseId);

        if(!$course){
            return response()->json([
                'status' => 'error',
                'message' => 'course not found'
            ], 404);
        }

        $userId = $request->input('user_id');
        $user = getUser($userId);

        if($user['status'] == 'error'){
            return response()->json([
                'status' => $user['status'],
                'message' => $user['message']
            ], $user['http_code']);
        }

        $isExistMyCourse = MyCourse::where('course_id', '=', $courseId)
        ->where('user_id', '=', $userId)
        ->exists();

        if($isExistMyCourse){
            return response()->json([
                'status' => 'error',
                'message' => 'user already take this course'
            ], 409);
        }

        // // midtrans
        // if($course->type === 'premium'){
        //     if($course->price == 0){
        //         return response()->json([
        //             'status' => 'error',
        //             'message' => 'Price can\'t be 0'
        //         ], 405);
        //     }

        //     $order = postOrder([
        //         'user' => $user['data'],
        //         'course' => $course->toArray()
        //     ]);

        //     if($order['status'] == 'error'){
        //         return response()->json([
        //             'status' => $order['status'],
        //             'message' => $order['message']
        //         ], $order['http_code']);
        //     }

        //     return response()->json([
        //         'status' => $order['status'],
        //         'data' => $order['data']
        //     ]);
        // }else{
            $myCourse = MyCourse::create($data);
            return response()->json([
                'status' => 'success',
                'data' => $myCourse
            ]);
        // }
    }

    public function createPremiumAccess(Request $request)
    {
        try {
            $data = $request->all();
            $isExistMyCourse = MyCourse::where('course_id', '=', $request->course_id)
            ->where('user_id', '=', $request->user_id)
            ->exists();

            if($isExistMyCourse){
                return response()->json([
                    'status' => 'error',
                    'message' => 'user already take this course'
                ], 409);
            }

            $myCourse = MyCourse::create($data);

            return response()->json([
                'status' => 'success',
                'data' => $myCourse
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 409);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
