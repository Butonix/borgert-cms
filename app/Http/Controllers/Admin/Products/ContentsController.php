<?php

namespace App\Http\Controllers\Admin\Products;

use Illuminate\Http\Request;
use App\Libraries\UploadHandler;
use App\Http\Controllers\Controller;
use App\Models\Admin\Products\Contents;
use App\Models\Admin\Products\Categorys;

class ContentsController extends Controller
{
    const UPLOAD_PATH = 'products/';
    const UPLOAD_ROUTE = 'admin.products.contents.upload';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $contents = Contents::sortable(['created_at' => 'desc'])->paginate(10);

        return view('admin.products.contents.index', ['contents' => $contents]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categorys = Categorys::all();

        return view('admin.products.contents.create', ['categorys' => $categorys]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'category_id' => 'required|integer',
            'title' => 'required',
            'description' => 'required',
            'status' => 'required|integer',
        ]);

        $content = new Contents();

        $content->category_id = $request->category_id;
        $content->title = $request->title;
        $content->description = $request->description;
        $content->information_technical = $request->information_technical;
        $content->price = $request->price;
        $content->price_per = $request->price_per;
        $content->code = $request->code;
        $content->status = (isset($request->status) ? 1 : 0);
        $content->seo_title = $request->seo_title;
        $content->seo_description = $request->seo_description;

        $content->save();

        $user = \Auth::User();
        $path_from = self::UPLOAD_PATH.'temp-'.$user->id.'/';
        $path_to = self::UPLOAD_PATH.$content->id;

        if (\Storage::disk('uploads')->exists($path_from)) {
            \Storage::disk('uploads')->move($path_from, $path_to);
        }

        \Session::flash('success', trans('admin/products.contents.store.messages.success'));

        return redirect()->route('admin.products.contents.index')->withInput();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $categorys = Categorys::all();
        $content = Contents::find($id);

        return view('admin.products.contents.edit', ['categorys' => $categorys, 'content' => $content]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'category_id' => 'required|integer',
            'title' => 'required',
            'description' => 'required',
            'status' => 'required|integer',
        ]);

        $content = Contents::find($request->id);

        $content->category_id = $request->category_id;
        $content->title = $request->title;
        $content->description = $request->description;
        $content->information_technical = $request->information_technical;
        $content->price = $request->price;
        $content->price_per = $request->price_per;
        $content->code = $request->code;
        $content->status = (isset($request->status) ? 1 : 0);
        $content->seo_title = $request->seo_title;
        $content->seo_description = $request->seo_description;

        $content->save();

        \Session::flash('success', trans('admin/products.contents.update.messages.success'));

        return redirect()->route('admin.products.contents.index')->withInput();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        if (is_null($request->contents)) {
            \Session::flash('info', trans('admin/products.contents.destroy.messages.info'));

            return redirect()->route('admin.products.contents.index');
        }

        Contents::destroy($request->contents);
        \Session::flash('success', trans('admin/products.contents.destroy.messages.success'));

        // Precisamos remover as imagens desse ID também
        // tem que ser um foreach porque é um array de galerias
        foreach ($request->contents as $id) {
            // Checamos se o diretório existe
            $path = self::UPLOAD_PATH.$id;

            // Deletamos o diretório da imagem
            if (\Storage::disk('uploads')->exists($path)) {
                \Storage::disk('uploads')->deleteDirectory($path);
            }
        }

        return redirect()->route('admin.products.contents.index');
    }

    /**
     * Faz o envio ou carrrega as imagens de um diretório.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request, $id = null)
    {
        $user = \Auth::User();

        $path = 'temp-'.$user->id;

        if (is_numeric($id)) {
            $path = $id;
        }

        $config = [
            'script_url' => route(self::UPLOAD_ROUTE, $path),
            'upload_dir' => config('filesystems.disks.uploads.root').'/'.self::UPLOAD_PATH.$path.'/',
            'upload_url' => url('/').'/uploads/'.self::UPLOAD_PATH.$path.'/',
            'delete_type' => 'GET',
        ];

        // Deletamos a imagem por GET
        if (isset($request->file)) {
            $file = self::UPLOAD_PATH.$path.'/'.$request->file;
            if (\Storage::disk('uploads')->has($file)) {
                \Storage::disk('uploads')->delete($file);
            }

            $thumb = self::UPLOAD_PATH.$path.'/thumbnail/'.$request->file;
            if (\Storage::disk('uploads')->has($thumb)) {
                \Storage::disk('uploads')->delete($thumb);
            }
        }

        new UploadHandler($config);

        return view('admin._inc.fileupload.empty');
    }
}
