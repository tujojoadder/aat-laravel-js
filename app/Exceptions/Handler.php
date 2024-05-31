<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Support\Facades\Log;
class Handler extends ExceptionHandler
{


 // The `report` method is called when an exception is caught for logging purposes.
 public function report(\Throwable $exception)
 {
     if ($exception instanceof ModelNotFoundException) {
         $this->handleModelNotFound($exception);
     } elseif ($exception instanceof RelationNotFoundException) {
         $this->handleRelationNotFound($exception);
     } elseif ($exception instanceof \Error) {
         $this->handleError($exception);
     } elseif ($exception instanceof \Exception) {
         $this->handleException($exception);
     }

     // Call the parent `report` method to perform the default logging behavior.
     parent::report($exception);
 }

 // The `render` method is called when an exception is caught to customize the response.
 public function render($request, \Throwable $exception)
 {
     if ($exception instanceof ModelNotFoundException) {
         return $this->handleModelNotFoundResponse($exception);
     } elseif ($exception instanceof RelationNotFoundException) {
         return $this->handleRelationNotFoundResponse($exception);
     } elseif ($exception instanceof \Error) {
         return $this->handleErrorResponse($exception);
     } elseif ($exception instanceof \Exception) {
         return $this->handleExceptionResponse($exception);
     }

     // Call the parent `render` method to perform the default rendering behavior.
     return parent::render($request, $exception);
 }

 // These methods contain the specific logic for handling each type of exception.

 protected function handleModelNotFound(\Throwable $e)
 {
     // Log an error message for ModelNotFoundException.
     Log::error('ModelNotFoundException: ' . $e->getMessage());
 }

 protected function handleRelationNotFound(\Throwable $e)
 {
     // Log an error message for RelationNotFoundException.
     Log::error('RelationNotFoundException: ' . $e->getMessage());
 }

 protected function handleError(\Throwable $e)
 {
     // Log an error message for general errors.
     Log::error('Error: ' . $e->getMessage());
 }

 protected function handleException(\Throwable $e)
 {
     // Log an error message for generic exceptions.
     Log::error('Exception: ' . $e->getMessage());
 }

 protected function handleModelNotFoundResponse(\Throwable $e)
 {
     // Return a JSON response for ModelNotFoundException.
     return response()->json(['error' => 'User not found'], 404);
 }

 protected function handleRelationNotFoundResponse(\Throwable $e)
 {
     // Return a JSON response for RelationNotFoundException.
     return response()->json(['error' => 'Invalid relationship'], 500);
 }

 protected function handleErrorResponse(\Throwable $e)
 {
     // Return a JSON response for general errors.
     return response()->json(['error' => 'Please try again later'], 500);
 }

 protected function handleExceptionResponse(\Throwable $e)
 {
    if ($e instanceof \Illuminate\Validation\ValidationException) {
        // Return a JSON response for validation errors.
        return response()->json(['error' => $e->validator->errors()->first()], 422);
    }

    // Return a JSON response for general errors.
    return response()->json(['error' => 'Please try again later'], 500);
 }


    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];



}
