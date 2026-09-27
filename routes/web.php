Route::get('/hello', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Backend Fixpoint Service Berhasil Berjalan!'
    ]);
});
