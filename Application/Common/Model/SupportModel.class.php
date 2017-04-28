<?php
use Think\Model;
/**
 * 赞
 */
class SupportModel extends BaseModel {
	public $cacheKey  = 'support_';
	public $typeArr = ['post'=>1,'new'=>'2'];
	protected $_validate;
	
	function __construct(){
		parent::__construct();
	}
	
	function setValidate($data){
		$this->_validate = [
			['user_id', 	 'require', 	'缺少用户id!', 1],
			['node_id', 	 'require', 	'缺少新鲜事id!', 1],
		];

		!$data['type']  && $data['type'] = 0;
		$con = [
			'type'	 =>	(int)$data['type'],
			'user_id'=>	(int)$data['user_id'],
			'node_id'=>	(int)$data['node_id'] 
		];

		if($this->where($con)->find())
			return $this->setError('你已经赞过了!');
		
		return $data;
	}
	
	//点赞
	public function support($type){
	    $data = [
            'user_id' => $this->user['id'],
            'type' => $this->typeArr[$type],
	        'node_id' =>$_GET['id']
        ];
	     
	    $info = $this->where($data)->find();
	    //当前用户有记录的时候
	    if($info){
	        if((int)$info['support'] == 1){
	            if($_GET['act'] == 'zan'){
	        
	                if($this->where($data)->delete())
	                    return ajaxReturn2(0,'取消赞成功',['status'=>1]);
	            }else{
	                $data['support'] = 0;
	                
	                if($this->edit($data))
	                    return ajaxReturn2(0,'踩成功',['status'=>4]);;
	            }
	        }
	         
	        if((int)$info['support'] == 0){
	            if($_GET['act'] == 'cai'){
	                 
	                if($this->where($data)->delete())
	                    return ajaxReturn2(0,'取消踩成功',['status'=>2]);
	            }else{
	                $data['support'] = 1;
	                if($this->edit($data))
	                    return ajaxReturn2(0,'赞成功',['status'=>3]);
	            }
	        }
	    }
	    
	    
	    //当前用户没有记录的时候
	    if($_GET['act'] == 'zan'){
	        $data['support'] = 1;
	        if($this->edit($data))
	                    return ajaxReturn2(0,'赞成功',['status'=>3]);
	    }
	    
	    if($_GET['act'] == 'cai'){
	        $data['support'] = 0;
	        if($this->edit($data))
	            return ajaxReturn2(0,'踩成功',['status'=>4]);
	    }
	     
	}
	/**
	 * 编辑or添加
	 */
	function edit($data){
		$data = $this->setValidate($data);
		if(!$this->create($data))
			return false;

		if(!($id = $this->add())){
			return 0;//操作失败
		}
		return 1;//操作成功
	}
	/**
	 * 赞的个数
	 * @param int $nid 节点id
	 * @param int $type 
	 * @param int $uid 用户id
	 * @return int 
	 */
	public function getNum($nid, $type = 0, $uid=null){
		$con = ['node_id'=>$nid, 'type'=>$type];
		$uid && $con['user_id'] = $uid;
		$num = $this->where($con)->count();	
		return $num;
	}
	
	public function getInfo($id){
		$info = $this->find($id);
		if(!$info) return;
	
		$info['typeName'] 	= $this->typeArr[$info['type']];
		$info['statusName'] = $this->statusArr[$info['status']];
		$info['addTime'] 	= local_date($info['add_time']);
		$info['updateTime'] = local_date($info['update_time']);
		$info['beginTime']  = local_date($info['begin_time']);
		$info['receiveTime']  = local_date($info['receive_time']);
		$info['refuseTime']  = local_date($info['refuse_time']);
		$info['reportTime']  = local_date($info['report_time']);
		$info['cancelTime']  = local_date($info['cancel_time']);
		
		$userMod = d('user');
		$user = $userMod->getInfo($info['user_id']);
		$artist = $userMod->getInfo($info['artist_id']);
		$info['user'] = $user;
		$info['artist'] = $artist;
		$info['artistId'] = $info['artist_id'];
		$info['artistName'] = $artist['realname'];
		$info['refuseTypeName'] = $this->refuseTypeArr[$info['refuse_type']];
		$info['reportTypeName'] = $this->reportTypeArr[$info['report_type']];
		
		return $info;
	}
	
	/**
	 * @param array $con
	 * @return array
	 **/
	public function getList($con, $limit = 50, $order = 'add_time desc'){
		$list = $this->where($con)->field('id')->limit($limit)->order($order)->select();
		foreach($list as $k=>$v){
			$list[$k] = $this->getInfo($v['id']);
		}
		return $list;
	}
	
	function getPageList($con, $fields = 'id', $order = '', $perNum = 15){
		$data = parent::getPageList($con, $fields, $order, $perNum);
		foreach($data['list'] as $k=>$v){
			$v = $this->getInfo($v['id']);
			$data['list'][$k] = $v;
		}
	
		return $data;
	}
	
}