<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;

class CommentController extends Controller
{
    public function hide(Comment $comment)
    {
        $comment->forceFill(['hidden_at' => now()])->save();

        return $comment;
    }

    public function unhide(Comment $comment)
    {
        $comment->forceFill(['hidden_at' => null])->save();

        return $comment;
    }
}
