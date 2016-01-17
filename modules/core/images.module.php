<?php
require_fullpath(__FiLE__, 'module.php');

abstract class tw_images extends gs_recordset_handler
{
    var $no_urlkey = 1;

    function src1($params, $record = null)
    {
        return trim(current($this->src($params, $record)));
    }

    function img1($params, $record = null)
    {
        return trim(current($this->img($params, $record)));
    }

    function src($params, $record = null)
    {
        $records = $record ? array($record) : $this;
        $ret = array();
        foreach ($records as $rec) {
            $ret = array_merge($ret, $rec->File->src($params));
        }
        return $ret;
    }

    function img($params, $record = null)
    {
        $records = $record ? array($record) : $this;
        $ret = array();
        foreach ($records as $rec) {
            $ret = array_merge($ret, $rec->File->img($params));
        }
        return $ret;
    }

    function href($img, $href, $record = null)
    {
        $records = $record ? array($record) : $this;
        $ret = array();
        $rel = 'hrefgrp_' . $records->first()->Parent_id;
        foreach ($records as $rec) {
            $h=$rec->File->src($href);
            $h = reset($h);
            $i = $rec->File->img($img);
            $i = reset($i);
            $ret[] = sprintf('<a class="images_href" href="%s" rel="%s">%s</a>', $h, $rel, $i);
        }
        return $ret;

    }

    function imghref($params, $hrefparams, $record = null)
    {
        $rnd = md5(serialize($hrefparams));
        $records = $record ? array($record) : $this;
        $ret = array();
        foreach ($records as $rec) {
            $ret[] = sprintf('<a class="fancybox" rel="gallery_%s" href="%s">%s</a>', $rnd, $this->src1($hrefparams, $rec), $this->img1($params, $rec));
        }
        return $ret;
    }

    function record_as_string($rec)
    {
        $res = $rec->File->img('small');
        $res = trim(implode(' ', $res));
        return $res;
    }

    public function __toString()
    {
        return implode(' ', $this->recordset_as_string_array());
        //return 'image';
    }
}

abstract class tw_file_images extends gs_recordset_short
{
    var $no_urlkey = 1;
    var $gs_connector_id = 'file_public';
    var $config = array();
    var $fields = array(
        'File' => "fFile 'Файл' required=false",
        'resize_options' => 'fObject',
    );

    function __construct($f = array(), $init_opts = false)
    {
        parent::__construct(array_merge(is_array($f) ? $f : array(), $this->fields), $init_opts);
        $this->config_previews();
        $this->structure['triggers']['after_insert'] = 'resize';
        $this->structure['triggers']['after_update'] = 'resize';
    }

    function fetch_image($url)
    {
        $ret = array();
        $is = getimagesize($url);
        if (!$is || strpos($is['mime'], 'image') !== 0) return $ret;
        $i = file_get_contents($url);
        if (!$i) return $ret;
        $ret['File_mimetype'] = $is['mime'];
        $ret['File_width'] = $is[0];
        $ret['File_height'] = $is[1];
        $ret['File_filename'] = basename($url);
        $ret['File_data'] = $i;
        $ret['File_size'] = strlen($ret['File_data']);
        return $ret;
    }

    function src1($params, $record = null)
    {
        return trim(reset($this->src($params, $record)));
    }

    function img1($params, $record = null)
    {
        return trim(reset($this->img($params, $record)));
    }

    function img($params, $record = null)
    {
        $ret = $this->src($params, $record);
        foreach ($ret as $k => $v) {
            $ret[$k] = sprintf('<img src="%s" alt="">', $v);
        }
        return $ret;
    }

    function src($params, $record = null)
    {
        if (is_array($params)) {
            $type = $params[0];
        } else {
            $type = $params;
        }

        $records = $record ? array($record) : $this;
        $ret = array();
        $fname = $this->get_connector()->www_root . '/' . $this->db_tablename;
        foreach ($records as $rec) {
            $ret[] = $fname . '/' . $this->get_connector()->split_id($rec->get_id(), true) . '/' . (($type == '') ? 'File_data' : $type . '.jpg');
        }
        return $ret;
    }

    function config_previews()
    {
        $this->config = array(
            'admin' => array('width' => 100, 'height' => 100, 'method' => 'use_fields', 'bgcolor' => array(255, 255, 255)),
            'small' => array('width' => 100, 'height' => 75, 'method' => 'use_crop', 'bgcolor' => array(255, 255, 255)),
            //'orig'=>array('width'=>0,'height'=>0,'method'=>'copy'),
        );
    }

    function show($type, $rec)
    {
        $fname = $this->get_connector()->root . DIRECTORY_SEPARATOR . $this->db_tablename . DIRECTORY_SEPARATOR . $this->get_connector()->split_id($rec->get_id()) . DIRECTORY_SEPARATOR . $type . '.jpg';
        ob_end_clean();
        header('Content-Type: image/jpeg');
        readfile($fname);
        die();
    }

    function get_path($d, $rec)
    {
        return $fname = $this->get_connector()->root . DIRECTORY_SEPARATOR . $this->db_tablename . DIRECTORY_SEPARATOR . $this->get_connector()->split_id($rec->get_id()) . DIRECTORY_SEPARATOR;
    }


    function resize($rec, $type = null, $ret = null, $no_rewrite = false)
    {
        $rs = new img_resizes_cfg;
        $rs->find_records(array());
        foreach ($rs as $r) {
            $this->config[$r->name] = array(
                'width' => $r->width,
                'height' => $r->height,
                'method' => $r->method,
                'modifier' => $r->modifier,
                'bgcolor' => array_map('trim', explode(',', $r->bgcolor)),
            );


        }
        $fname = $this->get_connector()->root . DIRECTORY_SEPARATOR . $this->db_tablename . DIRECTORY_SEPARATOR . $this->get_connector()->split_id($rec->get_id()) . DIRECTORY_SEPARATOR;
        $sname = $fname . 'File_data';

        if (!$rec->first()->File_width) {
            $orname = $fname . 'orig.jpg';
            $data = $rec->get_recordset()->fetch_image($orname);
            if ($data) {
                $rec->fill_values($data);
                $rec->commit();
            }
        }
        ksort($this->config);
        foreach ($this->config as $key => $data) {
            //$gd=new vpa_gd($sname);
            $gd = new vpa_gd($rec->File_data, false);
            $iname = $fname . $key . '.jpg';
            //if ($data['width']>0  && ($data['width']<$rec->first()->File_width || $data['height']<$rec->first()->File_height)) {
            if ($data['width'] > 0) {
                if (isset($data['bgcolor']) && $data['bgcolor']) $gd->set_bg_color($data['bgcolor'][0], $data['bgcolor'][0], $data['bgcolor'][0]);
                if (isset($data['modifier']) && $data['modifier']) $gd->modifier($data['width'], $data['height'], $data['modifier']);

                $gd->new_width = min($data['width'], $gd->old_width);
                $gd->new_height = min($data['height'], $gd->old_height);


                switch ($data['method']) {
                    case 'use_width':
                        $gd->make_width();
                        break;
                    case 'use_height':
                        $gd->make_height();
                        break;
                    case 'use_box':
                        $gd->make_box();
                        break;
                    case 'use_space':
                        $gd->make_space();
                        break;
                    case 'use_fields':
                        $gd->make_fields();
                        break;
                    case 'use_crop':
                        if ($rec->resize_options && is_array($rec->resize_options) && isset($rec->resize_options['crop'])) {
                            $co = $rec->resize_options['crop'];
                            $gd->modifier_user_crop($co);
                        }
                        $gd->make_crop(array('position', '0', '0'));
                        break;
                }
            }
            if (!file_exists($iname) || ($no_rewrite == false && file_exists($iname))) {
                if (isset($data['method']) && $data['method'] == 'copy') {
                    //copy($sname,$iname);
                    file_put_contents($iname, $rec->File_data);
                } else {
                    $gd->save($iname, 100);
                }
            }
        }
    }

}

class images_handler extends gs_base_handler
{


    function resize($data = null)
    {
        $c = cfg('gs_connectors');
        $cinfo = $c[$this->params['key']];
        $d = $this->data['gspgid_va'];
        $rs = reset($d);
        $t = pathinfo(array_pop($d));
        $type = $t['filename'];
        $key = array_pop($d);
        if (strlen($key) == 0) {
            header('HTTP/1.1 404 Not Found');
            die();
        }
        $o = new $rs;
        load_dbdriver('file');
        $c = new gs_dbdriver_file($cinfo);
        $id = $c->id2int($key);
        $rec = $o->get_by_id($id);
        if (!$rec) {
            header('HTTP/1.1 404 Not Found');
            die();
        }
        $o->resize($rec, '');
        $o->show($type, $rec);
    }

    function show($data)
    {
        if (count($this->data['gspgid_va']) < 5) {
            $url = preg_replace("|\..+|is", "", $this->data['gspgid_va'][0]);
            $data = base64_decode($url);

            $data = preg_replace("|\..+|is", "", $data);
            $data = explode("/", $data);
        }
        $method = array(
            'w' => 'use_width',
            'h' => 'use_height',
            'b' => 'use_box',
            'f' => 'use_fields',
            'c' => 'use_crop',
        );
        $data[4] = preg_replace("|\..+|is", "", $data[4]);
        $rec = new $data[0]();
        $rec = $rec->get_by_id($data[4]);
        $file = $rec->File->first();
        $fdata = $file->File_data;
        if (strlen($fdata) == 0) {
            $fname = $file->get_connector()->root . DIRECTORY_SEPARATOR . $file->get_recordset()->db_tablename . DIRECTORY_SEPARATOR . $file->get_connector()->split_id($file->get_id()) . DIRECTORY_SEPARATOR . 'orig.jpg';
            md($fname, 1);
            $fdata = load_file($fname, false, true);
        }
        $txt = get_output();
        $gd = new vpa_gd($fdata, false);
        if ($data[2] > 0 && ($data[2] < $file->File_width || $data[3] < $file->File_height)) {
            $gd->set_bg_color(255, 255, 255);
            $gd->resize($data[2], $data[3], $method[$data[1]]);
        }
        $gd->show();
        //gs_logger::dump();
        exit();
    }

    function s()
    {
        return ($this->show($this->data['gspgid_va']));
    }


    function proxy($ret)
    {
        $proxy = cfg('images_handler_proxy');
        $proxy = $proxy[$this->params['id']];
        $filename = $this->data['gspgid_va'][0];
        $targetname = $this->data['gspgid_va'][0];
        if ($proxy['filename_regexp']) $filename = preg_replace($proxy['filename_regexp'][0], $proxy['filename_regexp'][1], $filename);
        $filename = $proxy['source'] . $filename;
        md($filename, 1);

        $targetname = $proxy['target'] . $targetname;

        $gd = new vpa_gd($filename);
        $data = $proxy['resize'];
        if ($data['bgcolor']) $gd->set_bg_color($data['bgcolor'][0], $data['bgcolor'][0], $data['bgcolor'][0]);
        if ($data['modifier']) $gd->modifier($data['width'], $data['height'], $data['modifier']);

        $gd->resize($data['width'], $data['height'], $data['method']);
        check_and_create_dir(dirname($targetname));
        $gd->save($targetname, 85);
        //$gd->show();
        if (file_exists($targetname)) {
            return html_redirect($this->data['gspgid']);
        }
        //return html_redirect('/i/no-cover.png');
    }

}

class images_module extends gs_base_module implements gs_module
{
    function __construct()
    {
    }

    function install()
    {
        foreach (array(
                     'img_resizes_cfg',
                 ) as $r) {
            $this->$r = new $r;
            $this->$r->install();
        }
    }

    function get_menu()
    {
        $ret = array();
        $item = array();
        $item[] = '<a href="/admin/img_resizes/">Images</a>';
        $item[] = '<a href="/admin/img_resizes/img_resizes_cfg">Resizes</a>';
        $ret[] = $item;
        return $ret;
    }

    static function get_handlers()
    {
        $data = array(
            'get' => array(
                '/files' => 'images_handler.resize:key:file_public',
                'img/show' => 'images_handler.show',
                'img/s' => 'images_handler.s',
                '/admin/images' => 'admin_handler.many2one:{name:images.html}',
                '/admin/window_form' => 'admin_handler.many2one:{name:window_form.html}',
                '/admin/many2one' => 'admin_handler.many2one:{name:many2one.html}',

                '/admin/img_resizes/img_resizes_cfg' => array(
                    'gs_base_handler.show:name:adm_img_resizes_cfg.html',
                ),
                '/admin/img_resizes/img_resizes_cfg/delete' => array(
                    'gs_base_handler.delete:{classname:img_resizes_cfg}',
                    'gs_base_handler.redirect',
                ),
                '/admin/img_resizes/img_resizes_cfg/copy' => array(
                    'gs_base_handler.copy:{classname:img_resizes_cfg}',
                    'gs_base_handler.redirect',
                ),
            ),
            'handler' => array(
                '/admin/form/img_resizes_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:img_resizes_cfg:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ),
                '/admin/inline_form/img_resizes_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:img_resizes_cfg}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ),
            ),
        );
        return self::add_subdir($data, dirname(__file__));
    }

}

class img_resizes_cfg extends gs_recordset_short
{
    public $no_urlkey = true;
    public $no_ctime = true;
    public $orderby = "id";

    function __construct($init_opts = false)
    {
        parent::__construct(array(
            'name' => "fString name",
            'width' => "fInt 'Ширина'",
            'height' => "fInt 'Высота'",
            'method' => "fSelect 'Метод' values='use_width,use_height,use_box,use_space,use_fields,use_crop,copy'",
            'bgcolor' => "fString 'Цвет фона R,G,B' default='0,0,0'",
            'modifier' => "fSelect 'Модификатор' values=',check_and_rotate_left,check_and_rotate_right,watermark,blurred' required=false",
        ), $init_opts);
        $this->structure['fkeys'] = array();
    }
}


?>
