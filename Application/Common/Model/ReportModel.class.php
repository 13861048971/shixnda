<?php
use Think\Model;
class ReportModel extends BaseModel {
    public $statusArr = ['已处理', '待处理'];
    public $typeArr = ['post'=>1,'postComment'=>'2'];
	public $cacheKey  = 'report_';
	public $reportTypeArr = [ 1=>'联系不上(手机无法接听)','诈骗,提前收取费用',
		'信息违法虚假','涉黄违法','其他原因' ];
	protected $_validate;
	
	function __construct(){
		parent::__construct();
		
		$this->_validate = [
			['user_id', 	'require', 	'缺少用户id', 1],
			['node_id', 		'require', 	'缺少内容id', 1],
 			['type', 'require', 	'缺少类型!', 1],
		];
	}
	
	
// 	function report(){
	    
// 	}
	/**
	 * 编辑or添加
	 */
	function edit($data, $id=null){
	     
	    $isreport = [
                'user_id' => $data['user_id'],
                'type' => $data['type'],
                'node_id' =>$data['node_id'],
            ];
	    if($this->isReport($isreport)){
	        $this->lastError = '您已举报过该内容!';
	        return false;
	    }
	        
		if($id){
			$data['update_time'] = time();
			$return  = $this->data($data)->where('id=' . (int)$id)->save();
			if(!$return){
				$this->lastError = '修改失败!';
				return false;
			}
			return $id;
		}
		
		$data['update_time'] = $data['add_time'] = time();
		if(!$this->create($data)) 
			return false;
		if(!($id = $this->add()))
			return $this->setError('添加失败!');

		return $id;
	}
	
/**
 * 
 * @param unknown $id
 */
	function isReport($data){
	    return $this->where($data)->find();
	}
	
	public function getInfo($id){
		$info = $this->find($id);
		if(!$info) return;
		$info['addTime'] = local_date($info['add_time']);
		$info['updateTime'] = local_date($info['update_time']);
		$info['user'] = d('user')->getInfo($info['user_id']);;
		return $info;
	}
	
	function getNum($con){
		return $this->where($con)->count();
	}
	
	function getNumArr($idArr){
	    $num = $this->where(['node_id'=>['in', $idArr]])->group('node_id')
	       ->field('count(id) num, node_id')->select();
	    return $num;
	}
	/**
	 * @param array $con
	 * @return array
	 **/
	public function getList($con, $limit = 50, $order = 'id desc'){
		$list = $this->where($con)->field('id')->limit($limit)->order($order)->select();
		foreach($list as $k=>$v){
			$list[$k] = $this->getInfo($v['id']);
		}
		return $list;
	}
	
	function getPageList($con, $fields = 'id', $order = 'id desc', $perNum = 15){  
		$data = parent::getPageList($con, $fields, $order, $perNum);
		$userId = getIdArr($data['list'],'user_id');
		$userList = d('user')->where(['id'=>['in',$userId]])->select();
		
		foreach($data['list'] as $k=>$v){
			$data['list'][$k] = $this->parseRow($v);
			
			foreach ($userList as $k1 =>$v1){
			    if($v['user_id'] == $v1['id']){
			        isset($v1['nickname']) && $v1['nickname'] ? 
			        ($data['list'][$k]['userName'] = $v1['nickname']):($data['list'][$k]['userName'] = $v1['mobile']);
			    }
			}
		}
		return $data;
	}
	
	//格式化行
	public function parseRow($v){ 
	    $v['updateTime'] = date('Y-m-d H:i',$v['update_time']);
	    $v['addTime'] = date("Y-m-d H:i",$v['add_time']);
	    return $v;
	}
}