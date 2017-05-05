<?php
use Think\Model;
class BlockModel extends BaseModel {
    public $typeArr = [ //可选类型
        1 => '轮播图'
    ];
    
    public $statusArr = [  //可选的状态
        0 => '禁用',
        1 => '启用'
    ];
    
    protected $_validate;
    
    function __construct(){
        parent::__construct();
    
        $this->_validate = [
            ['cateName', 'require', '缺少分类!'],
           // ['type', [1,2,3], '缺少类型!', 1, 'in'],
        ];
    }
    
    //列表
    public function getList($con=[], $limit=5, $order='id desc'){
        $list = $this->where($con)->order($order)->limit($limit)->select();
        foreach ($list as $k=>$v){
            $list[$k] = $this->parseRow($v);
        }
	    return $list;
	}
	
	//详情
	public function getInfo($id){
	    $info = $this->find($id);
	    if(!$info) return;
	    $info = $this->parseRow($info);
	    //dd($info);
	    return $info;
	}
	//格式化行
	public function parseRow($v){
	    $cateRow = d('contentCate')->where([ 'id'=>$v['cate_id'] ])->find();
	    $v['cateName'] = $cateRow['name'];
	    $v['statusName'] = $this->statusArr[$v['status']];
	    $v['publishTime'] = date("Y-m-d H:i:s",$v['publish_time']);
	    $v['updateTime'] = date("Y-m-d H:i:s",$v['update_time']);
	    $v['addTime'] = date("Y-m-d H:i:s",$v['add_time']);
	    $v['content'] = json_decode($v['content'], true);
	    
	    foreach($v['content'] as $k2=>$v2){
	        $v['content'][$k2]['imageArr'] = ['image['.$k2.']', '', $v2['image']];
	    }
	    return $v;
	}
	
	//添加或编辑
	function edit($data, $id=null){
	    foreach ($data['url'] as $k=>$v){
	        $arr[$k] = ['url'=>$v, 'image'=>$data['image'][$k]];
	    }
	    $data['content'] = json_encode($arr);
	    if($id){
	        $data['update_time'] = time();
	        $return  = $this->data($data)->where('id=' . (int)$id)->save();
	        if(false === $return){
	            $this->lastError = '修改失败!';
	            return false;
	        }
	        
	        return $id;
	    }
	    
	    
	    $data['update_time'] = $data['add_time'] = time();
	    
	    if(!$this->create($data))
	        return false;
	
        if(!($id = $this->add())){
            return $this->setError('添加失败!');
        }
        return $id;
	}
	
	//分页
	public function getPageList($con=[], $fields = '*', $order = 'id desc', $perNum = 15){
	    $data = parent::getPageList($con, $fields, $order, $perNum);
	
	    foreach($data['list'] as $k=>$v){
	        $data['list'][$k] = $this->parseRow($v);
	    }
	    return $data;
	}
	
	public function getContentCateList($id,$i = 0){
	    if($id>0){
	        $contentCate = d('contentCate')->getInfo($id);//分类的信息
	        $pid = (int)$contentCate['pid'];//信息的父级id
	         
	        $this->cateList[$i] = $contentCate;
	         
	        $i +=1;
	        // var_dump($postCate);
	        $this->getContentCateList($pid,$i);
	         
	    }
	     
	    return  $this->cateList;
	}
}

