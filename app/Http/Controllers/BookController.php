<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
/**
 * @OA\Tag(
 *     name="Libros",
 *     description="Operaciones sobre libros (CRUD)"
 * )
 */
class BookController extends Controller
{
    // Crear un libro
    /**
     * @OA\Post(
     *     path="/api/books",
     *     summary="Crear un nuevo libro",
     *     tags={"Libros"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "secret"},
     *             @OA\Property(property="title", type="string", example="Mi primer libro"),
     *             @OA\Property(property="secret", type="string", example="Este es mi secreto")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Libro creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="secret", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autorizado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'secret' => 'required|string',
        ]);

        $book = Book::create([
            'user_id' => Auth::id(),  // Asociamos el libro al usuario autenticado
            'title' => $request->title,
            'secret' => $request->secret,
        ]);

        return response()->json($book, 201);
    }

    // Listar todos los libros (problema de seguridad aquí)
    // Listar todos los libros (sin mostrar el campo secreto)
    /**
     * @OA\Get(
     *     path="/api/books",
     *     summary="Listar todos los libros (sin secretos)",
     *     tags={"Libros"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de libros",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Mi primer libro"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-07T14:30:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-07T14:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autorizado")
     * )
     */
    public function index()
    {
        // Excluimos el campo 'secret' al listar todos los libros
        $books = Book::select('id', 'user_id', 'title', 'created_at', 'updated_at')->get();
        return response()->json($books);
    }

    // Mostrar un libro por su identificador
    // Mostrar un libro por su identificador (revelando el campo secreto)
    /**
     * @OA\Get(
     *     path="/api/books/{id}",
     *     summary="Obtener un libro por su identificador",
     *     tags={"Libros"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del libro",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalle del libro con el secreto",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Mi primer libro"),
     *             @OA\Property(property="secret", type="string", example="Este es mi secreto"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-07T14:30:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-07T14:30:00Z")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Libro no encontrado"),
     *     @OA\Response(response=401, description="No autorizado")
     * )
     */
    public function show($id)
    {
        // Obtener el usuario autenticado
        $user = auth('api')->user();
        Log::info('User ID: ' . $user->id . ' está intentando acceder');
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Registrar en los logs el ID del usuario autenticado y el ID del libro
        Log::info('User ID: ' . $user->id . ' está intentando acceder al libro ID: ' . $id);

        // Buscar el libro
        $book = Book::findOrFail($id);

        // Verificar si el usuario autenticado es el dueño del libro
        if ((int)$book->user_id !== $user->id) {
            // Registrar en los logs que el acceso fue denegado
            Log::info('Acceso denegado. Usuario ID: ' . $user->id . ' intentó acceder al libro ID: ' . $id);

            return response()->json([
                'error' => 'Unauthorized access to book information'
            ], 403); // Prohibir acceso
        }

        // Si es el propietario, devolver toda la información, incluyendo el campo secreto
        return response()->json($book);
    }
    /*public function show($id)
    {
        $book = Book::findOrFail($id); // Encuentra el libro por su ID

        // Verificar si el usuario autenticado es el dueño del libro
        if (auth()->id() !== $book->user_id) {
            // Si no es el dueño, devolvemos solo la información pública (sin el campo 'secret')
            return response()->json([
                'id' => $book->id,
                'user_id' => $book->user_id,
                'title' => $book->title,
                'created_at' => $book->created_at,
                'updated_at' => $book->updated_at,
            ]);
        }

        // Si es el dueño, devolvemos todos los detalles, incluido el campo 'secret'
        return response()->json($book);
    }*/

}
