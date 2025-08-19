<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResources;
use App\Models\Book;
use App\Models\Borrows;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\Else_;


class BookController extends Controller
{
    public function refillWallet(Request $request)
    {
        if (Auth()->user()->id !== 1) {
            return response()->json(['error' => 'UnAuthorized, Admin access only.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'value' =>'required|numeric|min:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::find($request->user_id);

        $user->wallet = $user->wallet + $request->value;
        $user->save();

        return response()->json([
            'message' => 'wallet refilled successfully',
            'user_id' => $user->id,
            'new_balance' => $user->wallet,
        ], 200);
    }
    public function addBook(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'about' => 'nullable|string',
            'type' => 'required|numeric',
            'author' => 'nullable|string',
            'pdf_price' => 'required|numeric',
            'count' => 'required'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        $book= Book::create(
            $validator->validated()
        );
        return response()->json([
            'message' => 'Book Added Successfully',
            'book' => $book
        ], 201);
    }


    public function updateBook(Request $request,$id) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'about' => 'nullable|string',
            'type' => 'required|numeric',
            'author' => 'nullable|string',
            'pdf_price' => 'required|numeric',
            'count' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $book = Book::find($id);
        if (!$book) {
            return response()->json([
                'message' => 'Book Not Found',
            ], 404);
        }
        $book->update($request->all());
        return response()->json([
            'message' => 'Book Added Successfully',
            'book' => $book
        ], 201);
    }

    public function deleteBook($id) {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Book Not Found',
            ], 404);
        }

        $book->delete();
        if ($book) {
            return response()->json([
                'message' => 'Book Deleted Successfully',
                'book' => null
            ], 201);
        }
    }


    public function getType($type) {
        $data =  Book::where('type','=',$type)->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'Book Not Found',
            ], 404);
        }
        return response()->json([
            'message' => 'Books as types',
            'type' => $data
        ], 201);
    }


    public function getAuthor($author) {
        $data = Book::where('author',$author)->get();

        if($data->isEmpty()) {
            return response()->json([
                'message' => 'book not found',
            ], 404);
        }
        return response()->json([
            'message' => 'books as authors',
            'author' => $data
        ], 201);
    }

    public function getRandom() {
        $data = Book::inRandomOrder()->first();

        return response()->json([
            'message' => 'random book',
            'book' => $data
        ], 201);
    }

    public function getBook($id) {
        $book = Book::find($id);
        if (!$book) {
            return response()->json([
                'message' => 'book not found',
            ], 404);
        }
        return response()->json([
            'message' => 'this is book',
            'book' => $book
        ],201);
    }

    public function getNameBook(Request $request) {
        $data = Book::where('name','=',$request->name)->get();

        if($data->isEmpty()) {
            return response()->json([
                'message' => 'book not found',
            ], 404);
        }
        return response()->json([
            'message' => 'books as name',
            'name' => $data
        ], 201);
    }

    public function getAllBook() {
        $data=Book::all();
        if($data->isEmpty()) {
            return response()->json([
                'message' => 'Book not found',
            ], 404);
        }
        return response()->json([
            'message' => 'All Books',
            'book' => $data
        ], 201);
    }

    public function getDownloadBook(Request $request)
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        $filePath = public_path('book/' . $request->name . '.pdf');

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->download(
            $filePath,
            $request->name . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function getUploadBook(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'book' => 'required|file|mimes:pdf|max:20480' // 20 MB limit
        ]);

        $filename = $request->name . '.pdf';

        // Ensure directory exists
        $destinationPath = public_path('book/');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        $request->file('book')->move($destinationPath, $filename);

        $bookURL = url('book/' . $filename);

        return response()->json([
            'url' => $bookURL
        ], 200);
    }

    public function OrderBook(Request $request , $book_id)
    {
        $user = \auth()->user();
        $admin = User::find(1);
        $book = Book::findOrFail($book_id);


        // Check if user has enough balance
        if ($user->wallet < $book->pdf_price) {
            return response()->json(['error' => 'You cannot buy this book'], 403);
        }

        DB::transaction(function () use ($user, $admin, $book) {
            // Create order
            $order = Order::create([
                'order_date' => Carbon::now()->toDateString(),
                'user_id'    => $user->id,
            ]);

            // Create order details
            DB::table('order_details')->insert([
                'price'     => $book->pdf_price,
                'order_date'=> Carbon::now()->toDateString(),
                'order_id'  => $order->id,
                'book_id'   => $book->id,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);

            // Deduct from user wallet
            DB::table('users')
                ->where('id', $user->id)
                ->update(['wallet' => $user->wallet - $book->pdf_price]);

            // Add to admin wallet
            DB::table('users')
                ->where('id', $admin->id)
                ->update(['wallet' => $admin->wallet + $book->pdf_price]);
        });

        return response()->json([
            'message' => 'Book purchased successfully',
            'book_id' => $book->id
        ], 201);
    }

    public function GetMyBook()
    {
        $user = Auth::user();
        $data = Order::where('user_id', '=', $user->id)->get();
        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'you dont have any book',
            ], 404);
        }
        return response()->json([
            'message' => 'Orders',
            'book' => $data
        ], 201);
    }

    public function AddToFavourite($book_id)
    {
        $user = Auth::user();
        $book = Book::find($book_id);
        if (!$book) {
            return response()->json([
                'message' => 'Book not found',
            ], 404);
        }
        Favorite::create(['book_id'=> $book_id, 'user_id'=> $user->id]);
        return response()->json([
            'message' => 'Done',
            'user' => $user->id,
            'book' => $book_id
        ], 201);
    }


    public function GetMyFavourite()
    {
        $user = Auth::user();
        $data = Favorite::where('user_id','=',$user->id)->get();
        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'you dont have any book',
            ], 404);
        }
        return response()->json([
            'message' => 'Favourite books',
            'book' => $data
        ], 201);
    }

    public function borrowBook(Request $request , $book_id)
    {
        $user = Auth::user();
        $book = Book::find($book_id);
        if(!$book) {
            return response()->json([
                'message' => 'book not found',
            ], 404);
        }
        Borrows::create(['book_id'=> $book_id,
            'user_id'=>$user->id,
            'borrow_date'=>$request->borrow_date,
            'return_date'=>$request->return_date,
            'is_returned'=>false
        ]);
        return response()->json([
            'message' => 'Done',
            'user' => $user->id,
            'book' => $book_id
        ], 201);
    }

    public function GetBorrowsBooks()
    {
        $user = Auth::user();
        $data = Borrows::where('user_id' , '=' , $user->id)->get();
        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'you dont have any book',
            ], 404);
        }
        return response()->json([
            'message' => 'borrows books',
            'book' => $data
        ], 201);
    }


}
