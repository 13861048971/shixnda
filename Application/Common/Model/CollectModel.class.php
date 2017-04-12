<?php
use Think\Model;
/**
 * 关注/喜欢/赞
 */
class CollectModel extends BaseModel {
	public $cacheKey  = 'collect_';
	public $typeArr = ['摄影师','套餐','任务'];
	public $statusArr = [ 1=>'关注','取消关注'];
	function __construct(){
		parent::__construct();
		$this->_validate = [
			['user_id', 	 'require', 	'缺少用户id!', 1],
			['node_id', 	 'require', 	'缺少要关注的对象!', 1]
		];
	}
	
	/**
	 * 编辑or添加
	 */
	function edit($data, $id=null){
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
	
	public function getInfo($id){
		$info = $this->find($id);
		if(!$info) return;
	
		$info['typeName'] = $this->typeArr[$info['type']];
		$info['addTime'] = local_date($info['add_time']);
		
		if(1 == $info['type']){
			$meal = d('meal')->getInfo($info['node_id']);
			$info['meal'] = $meal;
		}
		if(0 == $info['type']){
			$pho = d('pho')->getInfo($info['node_id']);
			$info['pho'] = $pho;
		}
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

	function getNum($nodeId, $type=0, $userId=null){
		$con = ['node_id'=>$nodeId, 'type'=>$type];
		$userId && $con['user_id'] = $userId;
		return (int)$this->where($con)->count();
	}
	
	//关注
	function collect($nodeId, $userId, $type = 0){
		if(!$nodeId) return $this->setError('缺少要关注的节点!');
		if(!$userId) return $this->setError('缺少用户id!');
		$con = ['type'=>$type, 'node_id'=> $nodeId, 'user_id'=>$userId];
		
		if($this->isCollect($nodeId, $userId, $type))
			return true;

		if(!$this->edit($con))
			return false;
		//更新关注数
		$this->updateNum($nodeId, $type);
		return true;
	}
	
	//取消关注
	function unCollect($nodeId, $userId, $type = 0){
		$con = ['type'=>$type, 'node_id'=> $nodeId, 'user_id'=>$userId];
		if(!$userId) return $this->setError('缺少用户id!');
		if($this->isCollect($nodeId, $userId, $type)){
			if(false === $this->where($con)->delete())
				return false;
			//更新关注数
			$this->updateNum($nodeId, $type);
			return true;
		}
		return $this->setError('关注不存在了!');
	}
	
	/**
	 *	是否关注
	 * @param int $nodeId
	 * @param int $uid
	 * @param int $type
	 * @return bool
	 */
	function isCollect($nodeId, $userId = 0, $type = 0){
		!$userId && $userId = (int)session('user.id');
		if(!$userId) return false;
		$con = ['type'=>$type, 'node_id'=> $nodeId, 'user_id'=>$userId];
		if($id = $this->where($con)->field('id')->getField('id'))
			return true;
		return false;
	}
	
	/**
	 * 更新关注数
	 */
	function updateNum($nodeId, $type){
		$d = ['id'=>$nodeId, 'attentioned_num'=> $this->getNum($nodeId, $type)];
		$type == 0 && d('pho')->save($d);
		$type == 1 && d('meal')->save($d);
	}
}