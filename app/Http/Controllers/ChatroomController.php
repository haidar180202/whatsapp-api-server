<?php

// app/Http/Controllers/ChatroomController.php

namespace App\Http\Controllers;

/**
 * @OA\Info(title="WhatsApp API", version="1.0.0")
 */
use Illuminate\Http\Request;
use App\Models\Chatroom;
use App\Models\Message;
use Illuminate\Support\Facades\Validator;
use App\Events\MessageSent;

class ChatroomController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/chatrooms",
     *     summary="Create a new chatroom",
     *     description="Creates a new chatroom with a given name and optional description",
     *     tags={"Chatrooms"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="General Chatroom"),
     *             @OA\Property(property="description", type="string", example="A place for general discussion"),
     *             @OA\Property(property="max_members", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Chatroom created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Chatroom created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="General Chatroom"),
     *                 @OA\Property(property="description", type="string", example="A place for general discussion"),
     *                 @OA\Property(property="max_members", type="integer", example=50),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-01T10:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     )
     * )
     */

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

     /**
     * @OA\Get(
     *     path="/api/chatrooms",
     *     summary="List all chatrooms",
     *     description="Fetches a list of all chatrooms",
     *     tags={"Chatrooms"},
     *     @OA\Response(
     *         response=200,
     *         description="List of chatrooms retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="General Chatroom"),
     *                     @OA\Property(property="description", type="string", example="A place for general discussion"),
     *                     @OA\Property(property="max_members", type="integer", example=50),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-01T10:00:00Z")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    
    public function listChatrooms()
    {
        $chatrooms = Chatroom::all();
        return response()->json(['chatrooms' => $chatrooms], 200);
    }

    // Anotasi dan metode untuk `enterChatroom`
    /**
     * @OA\Post(
     *     path="/api/chatrooms/{chatroomId}/enter",
     *     summary="Enter a chatroom",
     *     description="Allows a user to enter a chatroom",
     *     tags={"Chatrooms"},
     *     @OA\Parameter(
     *         name="chatroomId",
     *         in="path",
     *         required=true,
     *         description="ID of the chatroom",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Entered the chatroom successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chatroom not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Chatroom is full or User is already in the chatroom"
     *     )
     * )
     */

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
     
         // Periksa apakah user sudah tergabung di chatroom
         if ($chatroom->users()->where('user_id', $request->user_id)->exists()) {
             return response()->json(['message' => 'User is already in the chatroom'], 200);
         }
     
         // Periksa apakah jumlah anggota sudah mencapai batas maksimum
         if ($chatroom->users()->count() >= $chatroom->max_members) {
             return response()->json(['error' => 'Chatroom is full'], 400);
         }
     
         // Tambahkan user ke chatroom
         $chatroom->users()->syncWithoutDetaching($request->user_id);
     
         return response()->json([
             'message' => 'User entered the chatroom successfully',
             'chatroom_members' => $chatroom->users()->get()
         ], 200);
     }
     

    // Anotasi dan metode untuk `leaveChatroom`
    
    /**
     * @OA\Post(
     *     path="/api/chatrooms/{chatroomId}/leave",
     *     summary="Leave a chatroom",
     *     description="Allows a user to leave a chatroom",
     *     tags={"Chatrooms"},
     *     @OA\Parameter(
     *         name="chatroomId",
     *         in="path",
     *         required=true,
     *         description="ID of the chatroom",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Left the chatroom successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chatroom not found"
     *     )
     * )
     */

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
    
    // Anotasi dan metode untuk `sendMessage`
    /**
     * @OA\Post(
     *     path="/api/chatrooms/{chatroomId}/messages",
     *     summary="Send a message to a chatroom",
     *     tags={"Chat"},
     *     @OA\Parameter(
     *         name="chatroomId",
     *         in="path",
     *         required=true,
     *         description="Chatroom ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Hello everyone!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Message sent successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="chatroom_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="message", type="string", example="Hello everyone!"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-01T10:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chatroom not found"
     *     )
     * )
    */

    
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
    //     $message = new Message();
    //     $message->chatroom_id = $chatroom->id;
    //     $message->user_id = $request->user_id;
    //     $message->message = $request->message;
    //     $message->attachment_path = $attachmentPath;
    //     $message->save();

    //     // Trigger event untuk broadcast pesan
    //     event(new MessageSent($message));

    //     return response()->json(['message' => 'Message sent successfully', 'data' => $message], 201);
    // }

    // public function sendMessage(Request $request, $chatroomId)
    // {
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'message' => 'nullable|string',
    //         'attachment' => 'nullable|image',
    //     ]);

    //     $chatroom = Chatroom::find($chatroomId);
    //     if (!$chatroom) {
    //         return response()->json(['error' => 'Chatroom not found'], 404);
    //     }

    //     $attachmentPath = null;
    //     if ($request->hasFile('attachment')) {
    //         $path = $request->file('attachment')->store('picture', 'public');
    //         $attachmentPath = $path;
    //     }

    //     $message = Message::create([
    //         'chatroom_id' => $chatroomId,
    //         'user_id' => $request->user_id,
    //         'content' => $request->message,
    //         'attachment' => $attachmentPath,
    //     ]);

    //     event(new MessageSent($message));
    //     return response()->json(['message' => 'Message sent', 'data' => $message], 201);
    // }

    public function sendMessage(Request $request, $chatroomId)
    {
        // Validasi input
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4|max:20480', // Tambahkan validasi untuk jenis file
        ]);

        // Temukan chatroom berdasarkan ID
        $chatroom = Chatroom::find($chatroomId);
        if (!$chatroom) {
            return response()->json(['error' => 'Chatroom not found'], 404);
        }

        // Simpan file lampiran jika ada
        $attachmentPath = '';
        if ($request->hasFile('attachment')) {
            // Simpan lampiran di direktori yang sesuai
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        // Simpan pesan ke database
        $message = Message::create([
            'chatroom_id' => $chatroomId,
            'user_id' => $request->user_id,
            'content' => $request->message,
            'attachment' => $attachmentPath,
        ]);

        // Trigger event untuk broadcast pesan
        event(new MessageSent($message));

        return response()->json(['message' => 'Message sent successfully', 'data' => $message], 201);
    }


    // Anotasi dan metode untuk `listMessages`
    /**
     * @OA\Get(
     *     path="/api/chatrooms/{chatroomId}/messages",
     *     summary="List all messages in a chatroom",
     *     description="Fetches all messages from a specific chatroom",
     *     tags={"Messages"},
     *     @OA\Parameter(
     *         name="chatroomId",
     *         in="path",
     *         required=true,
     *         description="ID of the chatroom",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Messages retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="message", type="string", example="Hello everyone"),
     *                     @OA\Property(property="sender_id", type="integer", example=3),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-01T10:10:00Z")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

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

    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     description="Registers a new user account",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="token123456"),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */

     public function register(Request $request)
     {
         $request->validate([
             'name' => 'required|string|max:255',
             'email' => 'required|string|email|max:255|unique:users',
             'username' => 'required|string|max:255|unique:users',
             'password' => 'required|string|min:8',
         ]);
 
         $user = User::create([
             'name' => $request->name,
             'email' => $request->email,
             'username' => $request->username,
             'password' => Hash::make($request->password),
         ]);
 
         $token = $user->createToken('auth_token')->plainTextToken;
 
         return response()->json([
             'access_token' => $token,
             'token_type' => 'Bearer',
         ], 201);
     }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login user",
     *     description="Authenticates a user using either username or email and returns a token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="username_or_email", type="string", example="johndoe or john.doe@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="token123456"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="username", type="string", example="johndoe")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid login details"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */


    public function login(Request $request)
    {
        $request->validate([
            'username_or_email' => 'required|string',
            'password' => 'required|string',
        ]);

        // Mencari pengguna berdasarkan username atau email
        $user = User::where('username', $request->username_or_email)
                    ->orWhere('email', $request->username_or_email)
                    ->first();

        // Jika pengguna tidak ditemukan atau password tidak cocok
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }


}

