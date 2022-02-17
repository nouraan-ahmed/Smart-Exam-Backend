<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class faceDetectionController extends Controller
{


        /**
     * @OA\Post(
     *      path="/faceDetection",
     *      operationId="faceDetectionAPI",
     *      tags={"ML Models"},
     *      summary="face detection",
     *      description="Returns number of faces",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/FaceDetectionRequest")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Success!"),
     * @OA\Property(property="numberOfFaces", type="integer", example=6),)
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */

    
    public function faceDetection(Request $request) {
        if(auth()->user()->type != 'student') {
            return response()->json(['message' => 'Unauthorized!'], 400);
        }
        $studentId = auth()->user()->id;
        $rules = [
            'image' => 'required',
            'examId' => 'required',
        ];
    
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return response()->json(['message' => 'Error validating request body'], 400);
        }

        $examId = $request->examId;
        

        $response = Http::post('http://3.142.238.250/m1/detect', [
            'image_encode' => $request->image,
        ]);

        

        if($response->ok()) {
            if($response->status() != 200) {
                return response()->json(['message' => 'Failed to send image!'], 400);
            }
            else {
                $numberOfFaces = $response->object()->number_of_faces;
                $status = DB::table('examSession')->update(['exam_id' => $examId, 'student_id' => $studentId, 'numberOfFaces' => $numberOfFaces]);
                if($status) {
                    return response()->json(['message' => 'Success!', 'numberOfFaces' => $numberOfFaces]);
                }
                else {
                    return response()->json(['message' => 'Error!'], 400);
                }
            }
        } else {
            return response()->json(['message' => 'Error!'], 400);
        }
    }
}
