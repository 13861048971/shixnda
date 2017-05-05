<?php
use Think\Controller;
class PostController extends PublicController {
    
    public function _initialize(){
        parent::_initialize();
         
    }
    //获取帖子分类
	public function index(){ 
	    $hotList = d('post')->getList([], 7, 'click desc');
	    $newList = d('post')->getList([], 7, 'add_time desc');
        $list = d('postCate')->getList(['pid'=>'0', 'status'=>'1']);
        //dump($list);exit();
        foreach($list as $k1=>$v1){
            //dump($v1);exit();
            $list[$k1]['list'] = d('postCate')->getList(['pid'=>$list[$k1]['id'], 'status'=>'1']);
            foreach($list[$k1]['list'] as $k2=>$v2){
                $id = d('postCate')->where(['pid'=>$v2['id']])->getField('id');
                $idArr1 = [$v2['id'], $id];
                $con = [
                    'add_time'     => ['gt', strtotime(date("Y-m-d"))],
                    'post_cate_id' => ['in', $idArr1] 
                ];
                $list[$k1]['list'][$k2]['todayPostNum'] = d('post')->where($con)->count();
                $list[$k1]['list'][$k2]['mainPostNum'] = d('post')->where(['post_cate_id' => ['in', $idArr1]])->count();
                $list[$k1]['list'][$k2]['replyPostNum'] = d('post')->where(['post_cate_id' => ['in', $idArr1]])->sum('comment_num');
                $list[$k1]['list'][$k2]['postNum'] = $list[$k1]['list'][$k2]['mainPostNum'] + $list[$k1]['list'][$k2]['replyPostNum']; 
            }
        } 
        $this->assign('hotList', $hotList);
        $this->assign('newList', $newList);
        $this->assign('list', $list);
		$this->display();
	}
	 
	//获取帖子列表
	public function postList(){
	    
	    $con = [
	        'node_id' => (int)$_GET['post_cate_id']?$_GET['post_cate_id']:0,
	        'type' => d('tdk')->typeArr['contentCate']
	    ];
	    $tdkInfo = d('tdk')->tdkInfo($con);
	    if($tdkInfo)
	    $this->setTdk($tdkInfo['title'], $tdkInfo['keywords'], $tdkInfo['description']);
	    
	    //商户交流的帖子列表
	    $customList = d('post')->getList([], 6, 'add_time desc');
	    
	    $post_cate_id2 = $_GET['post_cate_id2'];
	    $post_cate_id3 = $_GET['post_cate_id3'];
	    //三级分类列表
	    $childrenList = d('postCate')->getList(['pid'=>$post_cate_id2, 'status'=>1]);
	    if($childrenList){
	        foreach($childrenList as $k=>$v){
	            $childrenList[$k]['count'] = d('post')->where(['post_cate_id'=>$v['id']])->count();
	            $idArr[$k] = $v['id'];
	        }
	        //二级分类名称
	        $name = d('postCate')->where(['id'=>$post_cate_id2])->getField('name');
	        $where['post_cate_id'] = array('in', $idArr);
	        $map['post_cate_id'] = array('eq', $post_cate_id2);
	        $map['_complex'] = $where;
	        $map['_logic'] = 'or';
	        if($post_cate_id2&&$post_cate_id3){
	            $data = d('post')->getPageList(['post_cate_id'=>$post_cate_id3, 'status'=>'1'], '*', 'add_time desc', 3);
	        }elseif($post_cate_id2){
	            $data = d('post')->getPageList([$map, 'status'=>'1'], '*', 'add_time desc', 3);
	        }
	    }
	    else{
	        $data = d('post')->getPageList(['post_cate_id'=>$post_cate_id2, 'status'=>'1'], '*', 'add_time desc', 3);
	    } 
	   
	    $this->assign('customList', $customList);
	    $this->assign('todayPostNum', $_GET['todayPostNum']);
	    $this->assign('mainPostNum', $_GET['mainPostNum']);
	    $this->assign('childrenList', $childrenList);
	    $this->assign('name', $name);
	    $this->assign('post_cate_id2', $post_cate_id2);
	    $this->assign('post_cate_id3', $post_cate_id3);
	    $this->assign('list', $data['list']);
	    $this->assign('user', $this->user);
	    $this->assign('pageVar', $data['pageVar']);
	    $this->display();
	}
	
	//获取帖子详情
	public function postDetail(){
	    
	    $con = [
	        'node_id' => (int)$_GET['id'],
	        'type' => d('tdk')->typeArr['post']
	    ];
	    $tdkInfo = d('tdk')->tdkInfo($con);
	    if($tdkInfo)
	    $this->setTdk($tdkInfo['title'], $tdkInfo['keywords'], $tdkInfo['description']);
	    
	    //商户交流的帖子列表
	    $customList = d('post')->getList([], 6, 'add_time desc');
	    
	    $id = $_GET['id'];
	    $p = $_GET['p'];
	    //判断帖子是二级分类还是三级分类
	    $post_cate_id = d('post')->where(['id'=>$id])->getField('post_cate_id');
	    $post_cate_pid = d('postCate')->where(['id'=>$post_cate_id])->getField('pid');
	    $post_cate_ppid = d('postCate')->where(['id'=>$post_cate_pid])->getField('pid');
	    if($post_cate_ppid == 0){
	        $idArr = ['post_cate_id2'=>$post_cate_id];
	    }else{
	        $idArr = ['post_cate_id2'=>$post_cate_pid, 'post_cate_id3'=>$post_cate_id];
	    }
	    $userId = d('post')->where(['id'=>$id])->getField('user_id');
	    $userRow = d('user')->where(['id'=>$userId])->find();//发帖人信息
	    $postRow = d('post')->getInfo($id);//帖子信息
	    $this->click('post',$id);//访问量+1
	    $con = ['post_id'=>$id];
	    if($_GET['viewHost']){
	       $con['user_id'] = $userRow['id'];
	    }
	    
	    $data = d('postComment')->getPageList($con, '*', 'add_time', 5);//帖子评论信息
	    //帖子评论信息的赞和踩状态
	    foreach ($data['list'] as $k=>$v){
	        $con = [
	            'node_id' => $v['id'], 
	            'user_id' => $this->user['id'], 
	            'type'    => d('support')->typeArr['postComment']
	            ];
	        $data['list'][$k]['isSupport'] = d('support')->where($con)->getField('support');//回复点赞状态
	        $data['list'][$k]['supportNum'] = d('support')->getNum($v['id'], d('support')->typeArr['postComment'], 1);//回复点赞数
	        $data['list'][$k]['notSupportNum'] = d('support')->getNum($v['id'], d('support')->typeArr['postComment'], 0);//回复踩数
	        $data['list'][$k]['isReport'] = d('report')->where(['node_id'=>$v['id'], 'user_id'=>$this->user['id']])->getField('id');//举报状态
	        $data['list'][$k]['reportNum'] = d('report')->getNum(['node_id'=>$v['id']]);//举报数
	    }
	    
	    
	    $postRow['replyNum'] = d('postComment')->where(['post_id'=>$id])->count();//帖子回复数 
	    $postRow['collectNum'] = d('collect')->getNum($id, d('collect')->typeArr['post'], '');//收藏数
	    //判断收藏状态
	    $postRow['isCollect'] = d('collect')->where(['node_id'=>$id, 'user_id'=>$this->user['id']])->getField('id');
	    $postRow['supportNum'] = d('support')->getNum($id, d('support')->typeArr['post'], 1);//点赞数
	    $postRow['notSupportNum'] = d('support')->getNum($id, d('support')->typeArr['post'], 0);//踩数
	    //判断点赞状态
	    $postRow['isSupport'] = d('support')->where(['node_id'=>$id, 'user_id'=>$this->user['id']])->getField('support');
	    //判断举报状态
	    $postRow['isReport'] = d('report')->where(['node_id'=>$id, 'user_id'=>$this->user['id']])->getField('id');
	    $postRow['reportNum'] = d('report')->getNum(['node_id'=>$id]);//举报数
	   
	    foreach($data['list'] as $k=>$v){
	        $data['list'][$k]['avatar'] = d('user')->where(['id'=>$v['user_id']])->getField('avatar');
	    } 
	    
	    $this->assign('p', $p);
	    $this->assign('customList', $customList);
	    $this->assign('pageVar', $data['pageVar']);
	    $this->assign('page', $data['page']);
	    $this->assign('proNum', $data['proNum']);
	    $this->assign('viewHost', $_GET['viewHost']);
	    $this->assign('idArr', $idArr);
	    $this->assign('userRow', $userRow);
	    $this->assign('list', $data['list']);
	    $this->assign('postRow', $postRow);
	    $this->assign('user', $this->user);
	    $this->display();
	}
	
    //帖子评论
    public function comment(){
        $data = [
            'user_id' => $this->user['id'],
            'post_id' => $_POST['post_id'],
            'content' => $_POST['content']
        ];
        
        $messageData = [
            'from_user_id' => $data['user_id'],
            'node_id' => $data['post_id'],
            'type' => d('userMsg')->typeArr['评论信息'],
            'user_id' => d('post')->where(['id'=>$data['post_id']])->getfield('user_id'),
        ]; 
        d('userMsg')->edit($messageData);
        
        $id = d('postComment')->edit($data);
        if(!$id){
            ajaxReturn(1, '评论失败',['id'=>$id]);
        } 
        
        ajaxReturn(0, '评论成功', ['id'=>$id]);
    }
	
    //主帖人信息
    public function userInfo(){
        $userRow = d('user')->where(['id'=>$_GET['userId']])->find();
    }
    
    //帖子收藏
    public function postCollect(){
        if(!$this->user['id'])
           return ajaxReturn2(1,'请先登录');
        d(collect)->collect('post');
    }
     
    //帖子点赞或者踩
    public function postSupport(){
        if(!$this->user['id'])
            return ajaxReturn2(1,'请先登录');
        d('support')->isSupport('post');
    }
    
    //帖子回复点赞或者踩
    public function postCommentSupport(){
        if(!$this->user['id'])
            return ajaxReturn2(1,'请先登录');
            d('support')->isSupport('postComment');
    }
    
    //帖子举报
    public function postReport(){
        if(!$this->user['id'])
            return ajaxReturn2(1,'请先登录');
        if($_POST && !empty($_POST)){
            $data = [
                'user_id' => $this->user['id'],
                'type' => d('report')->typeArr['post'],
                'node_id' =>$_POST['post_id'],
                'content' =>$_POST['content'],
                'status' =>1
            ];
          
            $id = d('report')->edit($data);
            if($id)
                return ajaxReturn2(0,'举报成功');
                return ajaxReturn2(1,d('report')->getError());
        }
        
    }
    
    //别人用户信息
    function personInfo(){
        $personInfo = d('user')->getPerson($_GET['userId']);//用户信息
        $data = d('post')->getPageList(['user_id'=>$_GET['userId']]);//用户主贴
        $repaly = d('postComment')->getPageList(['user_id'=>$_GET['userId']]);//回帖信息
        $this->assign('personReplay',$repaly['list']);  
        $this->assign('postList',$data['list']);
        $this->assign('person',$personInfo); 
        $this->display();
    }
    
    //用户主题
    function personTheme(){
        $data = d('post')->getPageList(['user_id'=>$_GET['id']]);
        $this->assign('list',$data['list']);
        $this->display();
    }
    
    //用户回复
    function personReplay(){
        $reply_id = $_POST['reply_id'];   
        $row = d('postComment')->where(['id'=>$reply_id])->find();
        $data = [
            'user_id'  => $this->user['id'],
            'post_id'  => $row['post_id'], 
            'content'  => $_POST['content'],
            'reply_id' => $reply_id
        ];
        
        $messageData = [
          'from_user_id' => $data['user_id'],
            'node_id' => $data['reply_id'],
            'type' => d('userMsg')->typeArr['回复信息'],
            'from_user_name' => $this->user['nickname'],
            'user_id' => d('postComment')->where(['id'=>$data['reply_id']])->getfield('user_id'),
        ];
        d('userMsg')->edit($messageData);
        
        $id = d('postComment')->edit($data);
        if(!$id)
            ajaxReturn(1, '回复失败', ['id'=>$id]);
        $reply = d('postComment')->getInfo($id);
        ajaxReturn(0, '回复成功', ['reply'=>$reply]); 
    }
    
}