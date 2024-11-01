<?php

// app/Http/Controllers/ChatroomController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chatroom;
use Illuminate\Support\Facades\Validator;
use App\Events\MessageSent;

class ChatroomController extends Controller
{

    public function createChatroom(Request $request)
    {
        // Validasi data input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'max_members' => 'required|integer|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Buat chatroom baru
        $chatroom = Chatroom::create([
            'name' => $request->name,
            'max_members' => $request->max_members,
        ]);

        return response()->json(['message' => 'Chatroom created successfully', 'chatroom' => $chatroom], 201);
    }

    public function listChatrooms()
    {
        $chatrooms = Chatroom::all();
        return response()->json(['chatrooms' => $chatrooms], 200);
    }

    public function enterChatroom(Request $request, $chatroomId)
    {
        // Validasi bahwa user ID disertakan dalam request
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Temukan chatroom berdasarkan ID
        $chatroom = Chatroom::find($chatroomId);
        if (!$chatroom) {
            return response()->json(['error' => 'Chatroom not found'], 404);
        }

        // Periksa apakah jumlah anggota sudah mencapai batas maksimum
        if ($chatroom->users()->count() >= $chatroom->max_members) {
            return response()->json(['error' => 'Chatroom is full'], 400);
        }

        // Tambahkan user ke chatroom jika belum tergabung
        $chatroom->users()->syncWithoutDetaching($request->user_id);

        return response()->json(['message' => 'User entered the chatroom successfully'], 200);
    }

    public function leaveChatroom(Request $request, $chatroomId)
    {
        // Validasi bahwa user ID disertakan dalam request
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
    
        // Temukan chatroom berdasarkan ID
        $chatroom = Chatroom::find($chatroomId);
        if (!$chatroom) {
            return response()->json(['error' => 'Chatroom not found'], 404);
        }
    
        // Hapus user dari chatroom
        $chatroom->users()->detach($request->user_id);
    
        return response()->json(['message' => 'User left the chatroom successfully'], 200);
    }
    
    // public function sendMessage(Request $request, $chatroomId)
    // {
    //     // Validasi input
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'message' => 'nullable|string',
    //         'attachment' => 'nullable|file',
    //     ]);

    //     // Temukan chatroom berdasarkan ID
    //     $chatroom = Chatroom::find($chatroomId);
    //     if (!$chatroom) {
    //         return response()->json(['error' => 'Chatroom not found'], 404);
    //     }

    //     // Simpan file lampiran jika ada
    //     $attachmentPath = null;
    //     if ($request->hasFile('attachment')) {
    //         $attachmentPath = $request->file('attachment')->store('attachments', 'public');
    //     }

    //     // Simpan pesan ke database
    //     $chatroom->messages()->create([
    //         'user_id' => $request->user_id,
    //         'message' => $request->message,
    //         'attachment_path' => $attachmentPath,
    //     ]);

    //     return response()->json(['message' => 'Message sent successfully'], 201);
    // }

    public function sendMessage(Request $request, $chatroomId)
    {
        // Validasi input
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file',
        ]);

        // Temukan chatroom berdasarkan ID
        $chatroom = Chatroom::find($chatroomId);
        if (!$chatroom) {
            return response()->json(['error' => 'Chatroom not found'], 404);
        }

        // Simpan file lampiran jika ada
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        // Simpan pesan ke database
        $message = new Message();
        $message->chatroom_id = $chatroom->id;
        $message->user_id = $request->user_id;
        $message->message = $request->message;
        $message->attachment_path = $attachmentPath;
        $message->save();

        // Trigger event untuk broadcast pesan
        event(new MessageSent($message));

        return response()->json(['message' => 'Message sent successfully', 'data' => $message], 201);
    }

    public function listMessages($chatroomId)
    {
        // Temukan chatroom berdasarkan ID
        $chatroom = Chatroom::find($chatroomId);
        if (!$chatroom) {
            return response()->json(['error' => 'Chatroom not found'], 404);
        }

        // Ambil pesan-pesan dalam chatroom
        $messages = $chatroom->messages()->with('user')->get();

        return response()->json(['messages' => $messages], 200);
    }


}

