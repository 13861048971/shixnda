<?php
use Think\Controller;
use Think\Verify;
class UserController extends PublicController{
	public $mod;
	public $userId;
	
	function _initialize(){
		parent::_initialize();
		$this->mod = D('User');
		$this->userId = $this->user['id'];
	}
	
	function index(){
		ajaxReturn2(0,'', ['user' => $this->user]);
	}
	
	//登陆
	function login(){
		if(IS_POST){
			$mobile = $_POST['mobile'];
			$password = $_POST['password'];
			$vercode = $_POST['vercode'];
			$userMod = d('user');
			
			if($password){
				if(!$userMod->login($mobile, $password))
					return ajaxReturn2(1, $userMod->getError());
			
				return ajaxReturn2(0, '登录成功!',['user'=>session('user') ]);
			}
			//第三方登陆
			if(!$userMod->login3($_POST))
				return ajaxReturn2($userMod->getErrorCode(), $userMod->getError());
			return ajaxReturn2(0, '登录成功!',['user'=>session('user') ]);
		}
		ajaxReturn2(1, '登录失败!');
	}
	
	//验证码
	function getvercode(){
		$tel = $_GET['mobile'];
		$mod = d('user');
		$vercode = $mod->getVercode($tel);
		if( !$vercode ){
			return ajaxReturn2(1, $mod->getError());
		}
		return ajaxReturn2(0, '已发送!');
	}
	
	//注册
	function regist(){
		if(IS_POST){
			$mobile = $_POST['mobile'];
			$pass = $_POST['password'];
			$vercode = $_POST['vercode'];
			$userMod = d('user');
			
			if($user = $userMod->regist($mobile, $pass, $vercode)){
				return ajaxReturn2(0, '注册成功!', ['user' => $user]);
			}
			
			return ajaxReturn2(1, $userMod->getError());
		}
	}
	
	//退出
	function logout(){
		$d = ['id' => $this->userId, 'last_logout'=>time()];
		$this->mod->data($d)->save();
		session('user', null);
		return ajaxReturn2(0, '已退出登录!');
	}
	
	//个人资料
	function profile(){
		if(IS_POST){
			$d = $_POST;
			$arr = ['sex','nickname','avatar','weixin_id', 'qq_id', 'city', 'birthday'];
			foreach($d as $k=>$v){
				if($k == 'sex')
					continue;
				
				if( !$d[$k] || !in_array($k, $arr) ){
					unset($d[$k]);
					continue;
				}
			}
			
			if(! ($userId = $this->mod->edit($d, $this->userId)) )
				return ajaxReturn2(1, $this->mod->getError());
			
			$user = $this->mod->getInfo($userId);
			return ajaxReturn2(0, '操作成功!',['user'=>$user]);
		}
	}
	
	//密码重置
	function passReset(){
		if($this->mod->passReset($_POST)){
			session('user', null);
			return ajaxReturn2(0, '重置成功,请重新登录');
		}
		return ajaxReturn2(1, $this->mod->getError());
	}
	
	//关注
	function attention(){
		$mod = d('collect');
		$type = (int)$_POST['type'];
		$status = (int)$_POST['status'];
		$nodeId = (int)$_POST['node_id'];
		
		if(1 == $status){
			if($mod->collect($nodeId, $this->userId, $type))
				return ajaxReturn2(0, '已关注!');
		}
		if(2 == $status){
			if($mod->unCollect($nodeId, $this->userId, $type))
				return ajaxReturn2(0, '已取消关注!');
		}
		ajaxReturn2(1, '操作失败,'.$mod->getError());
	}
	
	//邀约套餐
	function inviteMeal(){
		$mod = d('order');
		$data = $_POST;
		$meal = d('meal')->getInfo($data['node_id']);
		if(!$meal)
			return ajaxReturn2(1, '套餐不存在!');
		
		$data['type'] = 1;
		$data['user_id'] = $this->userId;
		if($id = $mod->edit($data)){
			$d = [
				'cate'      => 2,
				'node_id'	=> $id, 
				'from'		=> $data['user_id'], 
				'user_id'	=> $meal['pho_id'],
				'title' 	=> '发起了一个套餐订单 金额: ￥'. $meal['price'],
			];
			$d['content'] = $d['title'];
			d('userMsg')->edit($d);	
			
			
			return ajaxReturn2(0, '下单成功',['id'=>$id]);
		}
		return ajaxReturn2(1, $mod->getError());
	}
	
	//订单列表
	function myOrder(){
		$mod = d('order');
		$arr = [
			['in', [0]], //待付款
			['in', [1]], //待确认
			['in', [2]], //拍摄中
			['in', [3,4]], //已完成
			['in', [8,9]], //已关闭
		];
		$tab = $_GET['tab'];
		$tab = $arr[$tab];
		$tab && $con['status'] = $tab;
		$con['user_id'] = $this->userId; 
		$data = $mod->getPageList($con);
		
		ajaxReturn2(0, '', $data);
	}
	//订单详情
	function orderDetail(){
		$mod = d('order');
		$id = (int)$_GET['id'];
		$row = $mod->getInfo($id);
		
		if(!$row) 
			return ajaxReturn2(1, '订单不存在!');
		if($row['pho_id'] != $this->userId && $row['user_id'] != $this->userId)
			return ajaxReturn2(1, '非法操作!');
		return ajaxReturn2(0,null, ['order'=>$row]);
	}
	
	//用户退单
	function orderCancel(){
		$mod = d('order');
		if($mod->cancel($_POST)){
			return ajaxReturn2(0, '操作成功!');
		}
		return ajaxReturn2(1, $mod->getError());
	}
	
	//订单完工
	function orderDone(){
		$mod = d('order');
		if($mod->done($_POST)){
			return ajaxReturn2(0, '操作成功!');
		}
		return ajaxReturn2(1, $mod->getError());
	}
	
	//取支付参数
	function checkout(){
		$mod = d('order');
		$id = (int)$_REQUEST['id'];
		$pay = (int)$_REQUEST['pay'];
		if($param = $mod->payParam($id, $pay)){
			return ajaxReturn2(0,'',['param'=> $param]);
		}
		
		ajaxReturn2(1, $mod->getError());
	}
	
	function checkout2(){
		$mod = d('order');
		$id = (int)$_POST['id'];
		$pay = (int)$_POST['pay'];
		if($param = $mod->checkout($id, $pay)){
			return ajaxReturn2(0,'',['param'=> $param]);
		}
		
		ajaxReturn2(1, $mod->getError());
	}
	
	//订单举报
	function orderReport(){
		$mod = d('order');
		$id = (int)$_POST['id'];
		$row = $mod->getInfo($id);
		if(!$row) 
			return ajaxReturn2(1, '订单不存在!');
		if($row['user_id'] != $this->userId)
			return ajaxReturn2(1, '非法操作!');
		if($row['report_type'] > 0)
			return ajaxReturn2(1, '你已操作过了!');
		if($mod->report($id, $_POST['report_type'], $_POST['report_note']))
			return ajaxReturn2(0, '操作成功!');
		return ajaxReturn2(1, $mod->getError());
	}
	
	//举报摄影师
	function phoReport(){
		$mod = d('report');
		$d = $_POST;
		$d['user_id'] = $this->userId;
		
		if($d['user_id'] == $d['pho_id'])
			return ajaxReturn2(1, '你不能举报自己!');
		
		if($mod->edit($d))
			return ajaxReturn2(0, '举报成功!');
		return ajaxReturn2(1, $mod->getError());
	}
	
	//消息通知
	function message(){
		$mod = d('userMsg');
		$con = $_GET;
		$con['_complex'] = [
			'user_id' => $this->userId, 
			"find_in_set('{$this->user[msgType]}',type_id)" => ['gt', 0],
			'_logic'  => 'or',
		];
		
		$data = $mod->getPageList($con);
		
		ajaxReturn2(0,'', $data);
	}
	//消息详情
	function messageDetail(){
		$info = d('userMsg')->getInfo((int)$_GET['id']);
		$info = filter([$info], false,'')[0];
		ajaxReturn2(0,'', $info);
	}
	
	//标记消息为已读
	function messageRead(){
		$id = $_REQUEST['id'];
		$mod= d('user_msg');
		if($mod->read($id, $this->userId)){
			ajaxReturn2(0,'操作成功!');
		}
		ajaxReturn2(1,  $mod->getError());
	}
	//收藏
	function collect(){
		$con['user_id'] = $this->userId;
		$con['type'] = ['lt',1];
		$data = d('collect')->getPageList($con);
		
		$this->assign($data);
		$this->display();
	}
	
	//添加任务
	function taskAdd(){
		$mod = d('task');
		$data = $_POST;
		$data['user_id'] = $this->userId;
		if($id = $mod->edit($data)){
			return ajaxReturn2(0, '操作成功!',['id'=>$id]);
		}
		return ajaxReturn2(1, $mod->getError());
	}
	
	//任务详情
	function taskDetail(){
		if($id = (int)$_GET['id'])
			ajaxReturn2(0,'', ['task'=>d('task')->getInfo($id)]);
	}
	
	//任务取消
	function taskCancel(){
		$mod = d('task');
		if($mod->cancel($_POST))
			return ajaxReturn2(0,'操作成功!');
		
		return ajaxReturn2(1, $mod->getError());
	}
	
	//删除任务
	function taskDel(){
		$mod = d('task');
		if($id = (int)$_POST['id']){
			if($mod->where(['user_id' => $this->id, 'id' => $id])->delete())
				return ajaxReturn2(0,'操作成功!');
		}
		return ajaxReturn2(1,'操作失败!');
	}
	
	//投标列表
	function taskJoinList(){
		$task_id = (int)$_GET['task_id'];
		$con['task_id'] = $task_id;
		$mod = d('join');
		$data = $mod->getPageList($con);
		ajaxReturn2(0,'', $data);
	}
	
	//投标详情
	function taskJoinDetail(){
		$id = (int)$_GET['id'];
		$join = d('join')->getInfo($id);
		$task = $join['task'];
		if(in_array($this->userId, [$join['user_id'], $task['user_id']]))
			return ajaxReturn2(0,'', ['join'=>$join]);
		
		ajaxReturn2(1,'没有权限!');
	}
	
	//投标拒绝
	function taskJoinRefuse(){
		$mod = d('join');
		$data = $_POST;
		$data['user_id'] = $this->userId;
		if($mod->refuse($data)){
			return ajaxReturn2(0, '操作成功!');
		}
		return ajaxReturn2(1, $mod->getError());
	}
	
	//投标接受并下单
	function taskJoinReceive(){
		$mod = d('join');
		$data = $_POST;
		$data['user_id'] = $this->userId;
		if($order_id = $mod->receive($data)){
			return ajaxReturn2(0, '操作成功!', ['id'=>$order_id]);
		}
		return ajaxReturn2(1, $mod->getError());
	}
	
	//我的喜欢
	function myAttention(){
		$mod = d('collect');
		$con = $_GET;
		$con['user_id'] = $this->userId;
		$data = $mod->getPageList($con);
		ajaxReturn2(0,'',$data);
	}
	
	//我发布的任务
	function myTask(){
		$con = $_GET;
		$tabArr = [['lt', 1],['gt', 0]];
		$tab = $con['tab'];
		isset($tab) && $tabArr[$tab] && ($con['status'] = $tabArr[$tab]);
		$con['user_id'] = $this->userId;
		$data = d('task')->getPageList($con);
		$data['sortArr'] = ["默认排序",'出价排序','火热排序'];
		ajaxReturn2(0,'', $data);
	}
	
	//查看交易状态
	function tradeQuery(){
		$mod = d('order');
		$id = (int)$_GET['id'];
		if($res = $mod->tradeQuery($id))
			return ajaxReturn2(0,'', ['result' => $res ]);
		ajaxReturn2(1, $mod->getError());
	}
	
	//新消息
	function messageNew(){
		$arr = d('userMsg')->newMsgNum($this->userId);
		ajaxReturn2(0,'',['count'=>$arr]);
	} 
	
	private function checkVer($code, $id=''){
		$ver = new Verify();
		return $ver->check($code, $id);
	}
}
	