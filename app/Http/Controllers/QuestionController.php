<?php

namespace App\Http\Controllers;

use App\Models\ExamQuestion;
use App\Models\Option;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Exam;
use App\Models\QuestionOption;
use App\Models\QuestionTag;
use App\Models\Tag;

class QuestionController extends Controller
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
        $user = auth()->user();
        $queryTag = request('tag');
        $myQuestions = request('myQuestions');
        $questions = [];
        $qs = [];
        if ($queryTag) {
            $tag = Tag::where('name', 'LIKE', $queryTag . '%')->get()->first();
            $questions = $tag->questions;
            if ($myQuestions != NULL) {
                if ($myQuestions == "true") {
                    foreach ($questions as $q) {
                        if (($q->instructor_id == $user->id) && ($q->isHidden == false)) {
                            array_push($qs, $q);
                        }
                    };
                } else if ($myQuestions == "false") {
                    foreach ($questions as $q) {
                        if (($q->instructor_id != $user->id) && ($q->isHidden == false)) {
                            array_push($qs, $q);
                        }
                    };
                }
            } else {
                foreach ($questions as $q) {
                    if ($q->isHidden == false) {
                        array_push($qs, $q);
                    }
                };
            }
        } else {
            if ($myQuestions != NULL) {
                if ($myQuestions == "true") {
                    $questions = Question::latest('created_at')->where(['instructor_id' => $user->id, 'isHidden' => false])->get();
                } else if ($myQuestions == "false") {
                    $questions = Question::latest('created_at')->where('instructor_id', '<>', $user->id)->where(['isHidden' => false])->get();
                }
            } else {
                $questions = Question::latest('created_at')->where(['isHidden' => false])->get();
            }
            $qs = $questions;
        }

        foreach ($questions as $q) {
            $q->instructor->user;
            $q->tags;
            $q->QuestionOption->each(function ($m) {
                $m->option;
            });
        }

        return $qs;
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
     *      security={ {"bearer": {} }},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/StoreQuestionRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(
     * @OA\Property(property="question", type="object", ref="#/components/schemas/Question")
     * ),
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
                'answers'    => 'required|array',
                'answers.*'  => 'required|string|distinct',
                'tags'    => 'array',
                'tags.*'  => 'string|distinct',
                'correctAnswer' => 'string',
            ]);

            $question = Question::create([
                'questionText' => $fields['questionText'],
                'type' => $fields['type'],
                'isHidden' => false,
                'instructor_id' => $user->id
            ]);

            $answers = $fields['answers'];
            if ($request->has('tags')) {
                $tags = $fields['tags'];
            } else {
                $tags = [];
            }


            foreach ($tags as $a) {
                $taggs = Tag::where(['name' => $a])->first();
                if ($taggs != null) {
                    $tid = $taggs->id;
                } else {
                    $t = Tag::create([
                        'name' => $a
                    ]);
                    $tid = $t->id;
                }

                $qtags = QuestionTag::where(['question_id' => $question->id, 'tag_id' => $tid])->first();

                if ($qtags == null) {

                    $t = QuestionTag::create([
                        'question_id' => $question->id,
                        'tag_id' => $tid
                    ]);
                }
            }

            if ($fields['type'] == 'mcq') {
                foreach ($answers as $a) {
                    $answerss = Option::create([
                        'value' => $a,
                        'type' => $fields['type']
                    ]);

                    if ($fields['correctAnswer'] == $answerss->value) {
                        $mcqanswers = QuestionOption::create([
                            'question_id' => $question->id,
                            'id' => $answerss->id,
                            'isCorrect' => true
                        ]);
                    } else {
                        $mcqanswers = QuestionOption::create([
                            'question_id' => $question->id,
                            'id' => $answerss->id,
                            'isCorrect' => false
                        ]);
                    }
                }
            } else if ($fields['type'] == 'essay') {
                foreach ($answers as $a) {
                    $answerss = Option::create([
                        'value' => $a,
                        'type' => $fields['type']
                    ]);

                    $mcqanswers = QuestionOption::create([
                        'question_id' => $question->id,
                        'id' => $answerss->id,
                        'isCorrect' => true
                    ]);
                }
            }

            $question->QuestionOption->each(function ($m) {
                $m->option;
            });
            $question->tags;

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
        $question = Question::where('id', $id)->get()->first();
        $question->instructor->user;
        $question->tags;
        $question->QuestionOption->each(function ($m) {
            $m->option;
        });
        return response()->json(['question' => $question]);
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
        $user = auth()->user();
        $examQuestions = ExamQuestion::where(['question_id' => $id])->get();
        $exams = [];
        foreach ($examQuestions as $exQ) {
            array_push($exams, Exam::find($exQ->exam_id));
        }
        usort($exams, function ($a, $b) {
            return strcmp($a->startAt, $b->startAt);
        });


        $now = date("Y-m-d H:i:s");
        if (count($exams) > 0)
            $start_time = $exams[0]->startAt;
        else $start_time = 0;
        //return response([$now, $start_time]);
        if ($start_time != 0 && $now >= $start_time) {
            //create New Question Because this question is found in another prev exam
            if ($user->type == 'instructor') {

                $fields = $request->validate([
                    'questionText' => 'required|string|max:255',
                    'type' => 'required|string',
                    'answers'    => 'required|array|min:2',
                    'answers.*'  => 'required|string|distinct|min:2',
                    'correctAnswer' => 'required|string',
                ]);


                $question = Question::create([
                    'questionText' => $fields['questionText'],
                    'type' => 'mcq',
                    'instructor_id' => $user->id
                ]);

                $answers = $fields['answers'];
                if ($fields['type'] == 'mcq') {
                    foreach ($answers as $a) {
                        $answerss = Option::create([
                            'value' => $a,
                            'type' => $fields['type']
                        ]);

                        if ($fields['correctAnswer'] == $answerss->value) {
                            $mcqanswers = QuestionOption::create([
                                'question_id' => $question->id,
                                'id' => $answerss->id,
                                'isCorrect' => true
                            ]);
                        } else {
                            $mcqanswers = QuestionOption::create([
                                'question_id' => $question->id,
                                'id' => $answerss->id,
                                'isCorrect' => false
                            ]);
                        }
                    }
                } else if ($fields['type'] == 'essay') {
                    foreach ($answers as $a) {
                        $answerss = Option::create([
                            'value' => $a,
                            'type' => $fields['type']
                        ]);

                        $mcqanswers = QuestionOption::create([
                            'question_id' => $question->id,
                            'id' => $answerss->id,
                            'isCorrect' => true
                        ]);
                    }
                }

                $question->QuestionOption->each(function ($m) {
                    $m->option;
                });

                return response($question, 201);
            } else {
                return response()->json(['message' => 'There is no logged in Instructor'], 400);
            }
        } else {
            //we can update this question because it is not in one of the prev exams
            $questionn = Question::find($id);
            $answers = $questionn->options;

            if ($user->type == 'instructor') {

                $fields = $request->validate([
                    'questionText' => 'string|max:255',
                    'answers'    => 'array',
                    'answers.*'  => 'string|distinct',
                    'correctAnswer' => 'string',
                ]);

                $newanswers = [];

                if ($questionn->type == 'mcq') {

                    for ($i = 0; $i < $answers->count(); $i++) {
                        $correctAnswerid = 0;
                        $op = Option::where(['id' => (int)($answers[$i]->id)])->first();

                        if ($op->value == $request['correctAnswer'])
                            $correctAnswerid = $op->id;

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

                                $answers[$i]->QuestionOption->update([
                                    'isCorrect' => (int)($option->id == $correctAnswerid)
                                ]);
                            }
                        } else {
                            array_push(
                                $newanswers,
                                Option::where(['id' => $answers[$i]->id])->first()->value
                            );
                            $option = Option::where(['id' => $answers[$i]->id])->first();
                            if (isset(((object)$request)->correctAnswer)) {
                                $answers[$i]->QuestionOption->update([
                                    'isCorrect' => (int)($option->id == Option::where(['value' => $request['correctAnswer']])->first()->id)
                                ]);
                            }
                        }
                    }
                } else if ($questionn->type == 'essay') {

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

                            $answers[$i]->QuestionOption->update([
                                'isCorrect' => 1
                            ]);
                        } else {
                            array_push(
                                $newanswers,
                                Option::where(['id' => $answers[$i]->id])->first()->value
                            );
                            $option = Option::where(['id' => $answers[$i]->id])->first();
                            $answers[$i]->QuestionOption->update([
                                'isCorrect' => 1
                            ]);
                        }
                    }
                }


                $questionn->update([
                    'questionText' => $request['questionText'] ? $request['questionText'] : $questionn->questionText
                ]);
                $questionn->QuestionOption->each(function ($m) {
                    $m->option;
                });

                return response(['question' => $questionn], 200);
            } else {
                return response()->json(['message' => 'There is no logged in Instructor'], 400);
            }
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
        $examQuestions = ExamQuestion::where(['question_id' => $id])->get();
        $exams = [];
        foreach ($examQuestions as $exQ) {
            array_push($exams, Exam::find($exQ->exam_id));
        }
        usort($exams, function ($a, $b) {
            return strcmp($a->startAt, $b->startAt);
        });


        $now = date("Y-m-d H:i:s");
        if (count($exams) > 0)
            $start_time = $exams[0]->startAt;
        else $start_time = 0;

        if ($start_time != 0 && $now >= $start_time) {
            //We cannot delete only set is hidden to true
            if ($user->type == 'instructor') {
                $question = Question::where(['id' => $id])->first();
                if ($question == null) {
                    return response()->json(['message' => 'There is no Question with this id'], 200);
                }
                $question->update(['isHidden' => true]);
                $question->save();
                return response()->json(['message' => 'Question is Hidden'], 200);
            } else {
                return response()->json(['message' => 'There is no logged in Instructor'], 400);
            }
        } else {

            if ($user->type == 'instructor') {
                $question = Question::where(['id' => $id])->first();
                if ($question == null) {
                    return response()->json(['message' => 'There is no Question with this id'], 200);
                }
                $question->delete();
                return response()->json(['message' => 'Question Deleted'], 200);
            } else {
                return response()->json(['message' => 'There is no logged in Instructor'], 400);
            }
        }
    }
}