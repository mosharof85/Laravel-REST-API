<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TasksResource;
use App\Models\Task;
use App\Traits\HttpResponses;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use mysql_xdevapi\Exception;

class TasksController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TasksResource::collection(
            Task::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get()
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $request->validated($request->all());


        if(isset($request->image)){
            try{
                $imagePath = $this->saveImage($request->image);

                $task = Task::create([
                    'user_id' => Auth::user()->id,
                    'name' => $request->name,
                    'image' => $imagePath,
                    'description' => $request->description,
                    'priority' => $request->priority
                ]);

                return new TasksResource($task);
            }
            catch (\Exception $exception){
                return $this->error('', 'Invalid image', 200);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        return $this->isNotAuthorized($task) ? $this->isNotAuthorized($task) : new TasksResource($task);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTaskRequest $request, Task $task)
    {
        if(Auth::user()->id !== $task->user_id){
            return $this->error('', 'You are not authorized to make this request', 403);
        }

        $data = $request->validated();

        if(isset($request->image)){
            try{
                $data['image'] = $this->saveImage($request->image);

                // If there is an old image, delete it
                if ($task->image) {
                    $absolutePath = public_path($task->image);
                    File::delete($absolutePath);
                }
            }
            catch (\Exception $exception){
                return $this->error('', 'Invalid image', 200);
            }
        }

        $task->update($data);

        return new TasksResource($task);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        if(Auth::user()->id !== $task->user_id){
            return $this->error('', 'You are not authorized to make this request', 403);
        }

        $task->delete();

        // If there is an old image, delete it
        if ($task->image) {
            $absolutePath = public_path($task->image);
            File::delete($absolutePath);
        }
    }

    private function isNotAuthorized($task){
        if(Auth::user()->id !== $task->user_id){
            return $this->error('', 'You are not authorized to make this request', 403);
        }
    }

    private function saveImage($image){
        // Check if image is valid base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            // Take out the base64 encoded text without mime type
            $image = substr($image, strpos($image, ',') + 1);
            // Get file extension
            $type = strtolower($type[1]); // jpg, png, gif

            // Check if file is an image
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new \Exception('invalid image type');
            }
            $image = str_replace(' ', '+', $image);
            $image = base64_decode($image);

            if ($image === false) {
                throw new \Exception('base64_decode failed');
            }
        } else {
            throw new \Exception('did not match data URI with image data');
        }

        $dir = 'images/';
        $file = Str::random() . '.' . $type;
        $absolutePath = public_path($dir);
        $relativePath = $dir . $file;
        if (!File::exists($absolutePath)) {
            File::makeDirectory($absolutePath, 0755, true);
        }
        file_put_contents($relativePath, $image);

        return $relativePath;
    }
}
