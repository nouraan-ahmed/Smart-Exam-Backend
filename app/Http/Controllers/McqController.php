<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Option;
use App\Models\McqAnswer;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Mcq;

class McqController extends Controller
{
    /**
     * @OA\Get(
     *      path="/questions",
     *      operationId="getQuestionsList",
     *      tags={"Questions"},
     *      summary="Get list of Questions",
     *      description="Returns list of Questions",
     *      security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     * @OA\Property(property="Questions", type="array", @OA\Items(ref="#/components/schemas/Question"))
     * ),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function index()
    {
        return Question::all();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }


    /**
     * @OA\Post(
     *      path="/questions/create",
     *      operationId="storeQuestion",
     *      tags={"Questions"},
     *      summary="create question",
     *      description="Returns Question data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/StoreQuestionRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(
     * @OA\Property(property="question", type="object", ref="#/components/schemas/Question"),),
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


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $user = auth()->user();

        if ($user->type == 'instructor') {

            $fields = $request->validate([
                'questionText' => 'required|string|max:255',
                'type' => 'required|string',
                'mark' => 'string',
                'answers'    => 'required|array|min:2',
                'answers.*'  => 'required|string|distinct|min:2',
                'correctAnswer' => 'required|string',
            ]);


            $question = Question::create([
                'questionText' => $fields['questionText'],
                'type' => 'mcq'
            ]);

            if ($question->type == 'mcq') {
                Mcq::create([
                    'id' => $question->id
                ]);
            }

            $answers = $fields['answers'];

            foreach ($answers as $a) {
                $answerss = Option::create([
                    'value' => $a,
                    'type' => 'mcq'
                ]);

                if ($fields['correctAnswer'] == $answerss->value) {
                    $mcqanswers = McqAnswer::create([
                        'question_id' => $question->id,
                        'id' => $answerss->id,
                        'isCorrect' => true
                    ]);
                } else {
                    $mcqanswers = McqAnswer::create([
                        'question_id' => $question->id,
                        'id' => $answerss->id,
                        'isCorrect' => false
                    ]);
                }
            }
            return response($question, 201);
        } else {
            return response()->json(['message' => 'There is no logged in Instructor'], 400);
        }
    }

    /**
     * @OA\Get(
     *      path="/questions/{question}",
     *      operationId="getquestionDetails",
     *      tags={"Questions", "Exam"},
     *      summary="Get question details",
     *      description="Returns question details",
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     * @OA\Property(property="question", type="object", ref="#/components/schemas/Question")
     * ),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function show($id)
    {
        return response()->json(['question' => Question::where('id', $id)->get()->first()]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }


    /**
     * @OA\Put(
     *      path="/questions/{id}",
     *      operationId="editQuestion",
     *      tags={"Questions"},
     *      summary="Edit question",
     *      description="Returns Question data",
     *      security={ {"bearer": {} }},
     *      @OA\Parameter(
     *          name="id",
     *          description="Question id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=false,
     *          @OA\JsonContent(ref="#/components/schemas/StoreQuestionRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(
     * @OA\Property(property="question", type="object", ref="#/components/schemas/Question"),),
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     * @OA\Property(property="message", type="string", example="Failed to update question"),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\Property(property="message", type="string", example="Unauthenticated"),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $question = Mcq::find($id);
        $questionn = Question::find($id);
        $answers = $question->McqAnswers;
        $user = auth()->user();

        if ($user->type == 'instructor') {

            $request = $request->validate([
                'questionText' => 'string|max:255',
                'type' => 'string',
                'mark' => 'string',
                'answers'    => 'array|min:2',
                'answers.*'  => 'string|distinct|min:2',
                'correctAnswer' => 'string',
            ]);

            $newanswers = [];

            for ($i = 0; $i < $answers->count(); $i++) {
                if (isset(((object)$request)->answers[$i])) {
                    array_push(
                        $newanswers,
                        ((object)$request)->answers[$i]
                    );
                    $option = Option::where(['id' => $answers[$i]->id])->first();
                    $option->update([
                        'value' => ((object)$request)->answers[$i]
                    ]);
                    if (isset(((object)$request)->correctAnswer)) {
                        $answers[$i]->update([
                            'isCorrect' => (int)($option->id == Option::where(['value' => $request['correctAnswer']])->first()->id)
                        ]);
                    }
                } else {
                    array_push(
                        $newanswers,
                        Option::where(['id' => $answers[$i]->id])->first()->value
                    );
                    $option = Option::where(['id' => $answers[$i]->id])->first();
                    if (isset(((object)$request)->correctAnswer)) {
                        $answers[$i]->update([
                            'isCorrect' => (int)($option->id == Option::where(['value' => $request['correctAnswer']])->first()->id)
                        ]);
                    }
                }
            }


            $questionn->update([
                'questionText' => $request['questionText'] ? $request['questionText'] : $questionn->questionText,
                'type' => isset($request['type']) ? $request['type'] : $questionn->type,
                'mark' => isset($request['mark']) ? $request['mark'] : $questionn->mark
            ]);

            $question->McqAnswers->each(function ($e) {
                $e->option;
            });


            return response(['question' => $question], 200);
        } else {
            return response()->json(['message' => 'There is no logged in Instructor'], 400);
        }
    }


    /**
     * @OA\Delete(
     *      path="/questions/{id}",
     *      operationId="deleteQuestion",
     *      tags={"Questions"},
     *      summary="Delete existing question",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Question id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Successful operation"
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $user = auth()->user();

        if ($user->type == 'instructor') {
            $question = Question::where(['id' => $id])->first();
            $question->delete();
        } else {
            return response()->json(['message' => 'There is no logged in Instructor'], 400);
        }
    }
}
