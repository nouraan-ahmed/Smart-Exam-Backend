<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\GroupQuestion;
use App\Models\Question;
use App\Models\QuestionTag;
use App\Models\Tag;
use App\Models\ExamQuestion;
use App\Models\Exam;
use Illuminate\Http\Request;

class GroupQuestionController extends Controller
{
    // Create new question
    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->type == 'instructor') {

            $fields = $request->validate([
                'questionText' => 'string|max:255',
                'image' => 'image',
                'type' => 'required|string',
                'questions'    => 'required|array',
                'questions.*'  => 'required|distinct',
                'tags'    => 'array',
                'tags.*'  => 'string|distinct'
            ]);

            if (array_key_exists("image", $fields)) {
                $path = Storage::disk('s3')->put('questionImages', $fields['image']);
                $path = Storage::disk('s3')->url($path);
            }
            $question = Question::create([
                'questionText' => array_key_exists("questionText", $fields) ? $fields['questionText'] : NULL,
                'image' => array_key_exists("image", $fields) ? $path : NULL,
                'type' => $fields['type'],
                'isHidden' => false,
                'instructor_id' => $user->id
            ]);

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
            $questions = $fields['questions'];
            foreach ($questions as $q) {
                GroupQuestion::create([
                    'group_id' => $question->id,
                    'question_id' => $q,
                ]);
            }
            $question = Question::where(['id' => $question->id])->first();
            $question->tags;
            if ($question->type == "group") {
                $question->questions->each(function ($e) {
                    $e->tags;
                    $e->options;
                });
            }
            return response($question, 201);
        } else {
            return response()->json(['message' => 'There is no logged in Instructor'], 400);
        }
    }

    // Edit Question
    public function update(Request $request, $id)
    {
        if (auth()->user()->type != 'instructor') {
            return response()->json(['message' => 'Unauthorized!'], 403);
        }
        $user = auth()->user();
        $examQuestions = ExamQuestion::where(['question_id' => $id])->get();
        $exams = [];
        foreach ($examQuestions as $exQ) {
            array_push($exams, Exam::find($exQ->exam_id));
        }
        usort($exams, function ($a, $b) {
            return strcmp($a->startAt, $b->startAt);
        });

        $existingQuestion = Question::where(['id' => id])->get()->first();

        if ($existingQuestion) {
            if ($existingQuestion->instructor_id != auth()->user()->id) {
                return response()->json(['message' => 'Cannot edit a question that does not belong to you!'], 400);
            }
        } else {
            return response()->json(['message' => 'No question with this id !'], 400);
        }


        $now = date("Y-m-d H:i:s");
        if (count($exams) > 0)
            $start_time = $exams[0]->startAt;
        else $start_time = 0;
        //return response([$now, $start_time]);
        if ($start_time != 0 && $now >= $start_time) {
            //create New Question Because this question is found in another prev exam
            $fields = $request->validate([
                'questionText' => 'string|max:255',
                'image' => 'image',
                'type' => 'required|string',
                'questions'    => 'required|array',
                'questions.*'  => 'required|distinct',
                'tags'    => 'array',
                'tags.*'  => 'string|distinct'
            ]);

            if (array_key_exists("image", $fields)) {
                $path = Storage::disk('s3')->put('questionImages', $fields['image']);
                $path = Storage::disk('s3')->url($path);
            }
            $question = Question::create([
                'questionText' => array_key_exists("questionText", $fields) ? $fields['questionText'] : NULL,
                'image' => array_key_exists("image", $fields) ? $path : NULL,
                'type' => $fields['type'],
                'isHidden' => false,
                'instructor_id' => $user->id
            ]);

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
            $questions = $fields['questions'];
            foreach ($questions as $q) {
                GroupQuestion::create([
                    'group_id' => $question->id,
                    'question_id' => $q,
                ]);
            }
            $question = Question::where(['id' => $question->id])->first();
            $question->tags;
            if ($question->type == "group") {
                $question->questions->each(function ($e) {
                    $e->tags;
                });
            }
            return response($question, 201);
        } else {
            //we can update this question because it is not in one of the prev exams
            $questionn = Question::find($id);


            $fields = $request->validate([
                'questionText' => 'string|max:255',
                'image' => 'image',
                'questions'    => 'array',
                'questions.*'  => 'string|distinct'
            ]);

            if (array_key_exists("questions", $fields)) {
                $questions = $fields['questions'];
                GroupQuestion::where('group_id', $questionn->id)->delete();

                foreach ($questions as $q) {
                    GroupQuestion::create([
                        'group_id' => $questionn->id,
                        'question_id' => $q,
                    ]);
                }
            }

            if (array_key_exists("image", $fields)) {
                if ($questionn->image) {
                    $s = explode("/", $questionn->image);
                    Storage::disk('s3')->delete($s[3] . "/" . $s[4]);
                }
                $path = Storage::disk('s3')->put('questionImages', $fields['image']);
                $path = Storage::disk('s3')->url($path);
            }

            $questionn->update([
                'questionText' => array_key_exists("questionText", $fields) ? $request['questionText'] : $questionn->questionText,
                'image' => array_key_exists("image", $fields) ? $path : $questionn->image
            ]);

            $questionn->tags;
            if ($questionn->type == "group") {
                $questionn->questions->each(function ($e) {
                    $e->tags;
                });
            }

            return response(['question' => $questionn], 200);
        }
    }
}
