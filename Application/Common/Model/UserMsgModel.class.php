<?php
use Think\Model;
/**
 * 消息管理
 */
class UserMsgModel extends BaseModel {
    public $statusArr = [1=>'显示', 0=>'不显示'];
	public $cacheKey  = 'user_msg_';
	public $typeArr = [ //消息类型
	    '系统信息' => 0,
        '评论信息' => 1,
        '回复信息' => 2, 
        '站内信息' => 3,
	    '点赞信息' => 4,
    ];
	public $cateArr = ['通知','投标','订单'];

	protected $_validate;
	private $config;
	
	function setValidate($data){
		$this->_validate = [
			
			['content', 'require', '缺少内容!',1],
		];
		
		//只选了所有人
		if( 1 == $data['type2'] && !$data['type_id'] && $data['cate'] < 1){
			$data['type_id'] = array_merge($this->typeIdArr[2], $this->typeIdArr[3]);
		}
		
		if($data['type_id'] && is_array($data['type_id'])) {
			$arr = array_diff($data['type_id'], $this->typeArr);
			$data['type_id'] = implode(',', $arr);
		}

// 		if(!$data['cate'] && !$data['type_id'] && !$data['user_id'])
// 			return $this->setError('缺少推送人群!');
		
		return $data;
	}
	
	/**
	 * 发送消息给服务器
	 * @param int $id
	 * @return bool
	 */
	public function sendMesg($id){
		import('Common.Common.Umeng.Umeng');
		$data = $this->getInfo($id);

		if($data['cate'] < 1 && $data['typeName'] && $types = explode(',', $data['typeName'])){
			$arr = $types;
		}
		
		if($uid = $data['user_id'])
			$arr[] = 'user_'.$uid;
		
		$appkey = $this->config['IOSappkey'];
		$appsecret = $this->config['IOSappsecret'];
		$IOSumeng = new Umeng($appkey, $appsecret);
		$str = $IOSumeng->sendIOSGroupcast($data, $arr);
		$res = json_decode($str, 1);
		if('SUCCESS' != $res['ret']){
			$note = 'IOS 发送失败.';
		}
		
		$appkey = $this->config['ANDappkey'];
		$appsecret = $this->config['ANDappsecret'];
		$ANDumeng = new Umeng($appkey, $appsecret);
		$str = $ANDumeng->sendAndroidGroupcast($data, $arr);
		$res = json_decode($str, 1);
		if('SUCCESS' != $res['ret']){
			$note .= 'Android 发送失败.';
		}
		
		$d = ['id'=>$id, 'note'=> $note];
		if(!$note ){
			$d['note'] = '发送成功!';
		}
		$this->data($d)->save();
		return true;
		return $note?false:true;
	}
	
	/**
	 * 编辑or添加
	 */
	function edit($data, $id=null){	
	    //dump($data);exit();
	    $type = $this->typeArr;
	    $type = array_flip($type);
	    $data['type_name'] = $type[$data['type']];
	    (int)$data['user_id'] < 1 && $data['user_name'] = "所有用户";
	    (int)$data['user_id'] >= 1 && $data['user_name'] = d('user')->where(['id'=>$data['user_id']])->getfield('nickname');
	      
	    if((int)$data['from_user_id'] < 1){
	        $data['from_user_name'] = '系统信息';
	    }else{
	        $fromUser = d('user')->where(['id'=>$data['from_user_id']])->find();
	        empty($fromUser['nickname'])?($data['from_user_name'] = $fromUser['mobile']):($data['from_user_name'] = $fromUser['nickname']);
	    }
        
// 	    if(in_array($data['type'],[1,2,4,5]))
//             $data['content'] = $data['user_name'].'你有一条来自'.$data['from_user_name'].'的'.$data['type_name'];
// 	    if($data['type'] == 4)
// 	        $data['content'] = $data['from_user_name'].'赞了您发布的帖子';
// 	    if($data['type'] == 3)
//             $data['content'] = $data['from_user_name'].'对你说：'.$data['content'];
	    
        switch ($data['type']){
            case 1:
                $data['content'] = $data['user_name'].'你有一条来自'.$data['from_user_name'].'的'.$data['type_name'];
                break;
            case 2:
                $data['content'] = $data['user_name'].'你有一条来自'.$data['from_user_name'].'的'.$data['type_name'];
                break;
            case 3:
                $data['content'] = $data['from_user_name'].'对你说：'.$data['content'];
                break;
            case 4:
                $data['content'] = $data['from_user_name'].'踩了您';
                break;
            case 5:
                $data['content'] = $data['from_user_name'].'赞了您';
                break;
        }
	    if($data['type_name']!='点赞信息')
            $data = $this->setValidate($data);
		
		if($id){
			$data['update_time'] = time();
			$data['id'] = $id;
			if(!$this->create($data)) 
				return false;
			if(false === $this->save()){
				$this->lastError = '修改失败!';
				return false;
			}
			return $id;
		}
		
		$data['add_time'] = $data['update_time'] = time();
		if(!$this->create($data))
			return false;
		if(!($id = $this->add()))
            return $this->setError('发送失败!');
		if(!$this->sendMesg($id)){
			return $this->setError('发送消息失败!');
		}
		return $id;
	}
	
	
	public function getInfo($id){
		$info = $this->find($id);
		if(!$info) return;

		$info['updateTime'] = local_date($info['update_time'], 'Y-m-d H:i');
		$info['addTime'] 	= local_date($info['add_time'], 'Y-m-d H:i');
		$info['userName'] = d('user')->getInfo($info['user_id'])['username'];
		!$info['title'] && $info['title'] = $info['content'];

		$type = $info['type'];
		$info['typeName'] = $info['type_id'];
		$info['cateName'] = $this->cateArr[$info['cate']];
		
		if($info['from']){
			$from = d('user')->getInfo($info['from']);
			$info['from'] = filter([$from], 'id,nickname,realname,mobile,avatar')[0];
		}else{
			$info['from'] = [
				'realname' => '管理员',
				'nickname' => '管理员',
				'avatar' => '/Public/images/admin.jpg',
				'isAdmin' => true,
			];
			if($nickname = $this->config['nickname']){
				$info['from']['nickname'] = $info['from']['realname'] = $nickname;
			}
			if($avatar = $this->config['avatar'])
				$info['from']['avatar'] = $avatar;
		}
		
		$info['isRead'] = $this->read($id, $this->user['id'], false);
		
		if($info['cate'] == 2 && $info['node_id'])
			$info['artistImage'] = d('album')
				->where(['type_id'=>$info['node_id']])
				->order('id desc')->getField('path');
		
		
		
		return $info;
	}
	
	/**
	 * 标记消息为已读
	 * @param int $id
	 * @param int $userId
	 * @param bool $read 是否标记已读
	 * @param bool
	 */
	public function read($id, $userId, $read = true){
		$mod = d('user_msg_read');
		$msg = $this->find($id);
		
		if(!$msg || ($msg['user_id'] > 0 && $msg['user_id'] != $userId))
			return $this->setError('消息不存在或没有权限!');
		$con = ['msg_id' => $id, 'user_id' => $userId];
		if($row = $mod->where($con)->find())
			return true;
		
		if(!$read)
			return false;
		
		if($mod->add($con))
			return true;
		
		return false;
	}
	
	//是否已读
	public function isRead($row, $read=false){
		$id = (int)$row['id'];
		$str = cookie('msg-ids');
		$ids = array_filter(explode('-', $str));
		if($read && $id){
			$row['user_id'] > 0 && $row['status'] = 1;
			$row['user_id'] < 1 && $row['status'] += 1;

			//系统消息
			if($row['user_id'] == 0 && !in_array($id, $ids)){
				$ids[] = $id;
				cookie('msg-ids', implode('-', $ids));
			}
			return $this->edit($row, $id);
		}
		
		if ($row['user_id'] > 0 && $row['status']){
			return true;
		}
		if($row['user_id'] == 0 && in_array($id, $ids))
			return true;
		
		return false;
	}

	public function newMsgNum($uid){
		$uid = (int)$uid;
		
		$mod = d('user_msg_read');
		$map = ['user_id' => $uid];
		$subQuery = $mod->field('msg_id')->where($map)->buildSql();
		$con['_complex'] = [
			'user_id' => $uid, 
			"find_in_set('{$this->user[msgType]}',type_id)" => ['gt', 0],
			'_logic'  => 'or',
		];
		$con['_string'] = 'id not in '.$subQuery;
		$arr = $this->where($con)->group('cate')->getField('cate,count(id)',true);
		$arr = [
			'cate_0' => (int)$arr[0],
			'cate_1' => (int)$arr[1],
			'cate_2' => (int)$arr[2],
			'total'  => (int)array_sum($arr),
		];
		return  $arr;
	}
	
	/**
	 * 
	 * @param array $con      类型,默认是产品分类
	 * @return array
	 **/
	public function getList($con = null, $limit = 10){
		$list = $this->where($con)->order('rank')->limit($limit)->select();
		foreach($list as $k=>&$v){
			$v = $this->getInfo($v['id']);
		}
		return $list;
	}
	
	public function getPageList($con, $fields = 'id',$order = 'id desc', $perNum = 10){
	    
	    if($con['type'] == '帖子回复')
	        $con['type'] = ['in',[1,2]];
	    
		if(isset($con['title']) && $con['title']){
			$con['title'] = ['like', '%' . $con['title'] . '%'];
		}
		
 		$mod = d('user_msg_read');
		($uid = $this->user['id']) && ($map['user_id'] = $uid); 
		
		if(isset($con['isRead']) && $map){
			$is = $con['isRead'];
			$subQuery = $mod->field('msg_id as id')->where($map)->buildSql();
			$is === '0' && $con['_string'] = 'id not in '.$subQuery;
			$is === '1' && $con['_string'] = 'id in '.$subQuery;
		}
		if( MODULE_NAME == 'Home' && $map){
			$subQuery = $mod->field('msg_id as id')->where($map)->buildSql();
			$fields = '*, if((id in ' . $subQuery . '), 1,0) as readed';
			$order = 'readed asc,id desc';
		}else{
		    
		} 

		$data = parent::getPageList($con, $fields, $order, $perNum);
		
		if( MODULE_NAME == 'Admin'){
		    $from_id = getIdArr($data['list'],'from_user_id');
		    $user_id = getIdArr($data['list'],'user_id');
		    $idArr = array_merge($from_id,$user_id);
		    $userList = d('user')->where(['id'=>['in',$idArr]])->select();
		}else{ 
		    //把点赞和踩的消息取出来
		    $supportMsgList = [];
		    $commentMsgList = [];
		    foreach($data['list'] as $mk=>$mv){
		        if(in_array($mv['type'], [4,5])){
		            $supportMsgList[] = $mv;
		        }else{
		            $commentMsgList[] =$mv; 
		        }
		    }
		   
		    $postCommentIdArr = getIdArr($commentMsgList,'node_id');
		    $supportIdArr = getIdArr($supportMsgList,'node_id');
		    if($postCommentIdArr)
		        $postComentList = d('postComment')->where(['id'=>['in',$postCommentIdArr]])->select();//评论信息
		    if($supportIdArr)
		        $supportList = d('support')->where(['id'=>['in',$supportIdArr]])->select();//点赞信息
		}
	
		foreach($data['list'] as $k=>$v){
			$data['list'][$k] = $this->parseRow($v);
			if( MODULE_NAME == 'Admin'){
			    foreach ($userList as $k1=>$v1){
			        if($data['list'][$k]['user_id'] == $v1['id']){
			            if(isset($v1['nickname']) && $v1['nickname']){
			                $data['list'][$k]['user_name'] = $v1['nickname'];
			            }else{
			                $data['list'][$k]['user_name'] = $v1['mobile'];
			            }
			        }
			        if($data['list'][$k]['from_user_id'] == $v1['id']){
			            if(isset($v1['nickname']) && $v1['nickname']){
			                $data['list'][$k]['from_user_name'] = $v1['nickname'];
			            }else{
			                $data['list'][$k]['from_user_name'] = $v1['mobile'];
			            }    
			        }
			    }
			}else{   
			    if(in_array($v['type'], [1,2])){
			        //生成评论表url连接
			        foreach ($postComentList as $k2 => $v2){
			            if($v2['id'] == $v['node_id']){
			                //计算当前消息前面的该帖下面的所有消息
			                $con = [
			                    'id'=>['lt',$v['node_id']],
			                    'post_id'=>$v2['post_id']
			                ];
			                $count = d('postComment')->getPostCommentCount($con);
			                $v['p'] = ceil($count/15);
			                //生成url定位当前消息的内容行
			                $data['list'][$k]['url'] = U('post/postDetail',['msg_id'=>$v['id'],'p'=>$v['p'],'id'=>$v2['post_id']]).'#comment-'.$v['node_id'];
			            }
			        }
			    }elseif (in_array($v['type'],[4,5])){  
			        //生成点赞信息链接
			        if($supportList){
			            foreach ($supportList as $supk => $supv){   
			                if($supv['id'] == $v['node_id']){
			                    //计算当前消息前面的该帖下面的所有消息
			                    if($supv['type'] == 1){
			                        $data['list'][$k]['url'] = U('post/supportSkip',['msg_id'=>$v['id'],'supportType'=>'post','supportNodeId'=>$supv['node_id']]);
			                    }elseif ($supv['type'] == 2){
			                        $data['list'][$k]['url'] = U('post/supportSkip',['msg_id'=>$v['id'],'supportType'=>'postComment','supportNodeId'=>$supv['node_id']]);
			                    }
			                }
			            }
			        } 
			    }       
			}	
		}
		return $data;
	}
	
	//格式化行
	public function parseRow($v){
         if(MODULE_NAME == 'Home'){
            $v['readed']?($v['isRead'] = true):($v['isRead'] = false);
   
        }else{
            $type = $this->typeArr;
            $type = array_flip($type);
            $v['type_name'] = $type[$v['type']];
            (int)$v['user_id'] < 1 && $v['user_name'] = "所有用户";
            if((int)$v['from_user_id'] < 1)
                $v['from_user_name'] = '系统信息';
        } 
        strlen($v['content']) < 20?($v['contentThumb'] = $v['content']):($v['contentThumb'] = mb_substr($v['content'], 0,20));
	    $v['addTime'] = date('Y-m-d H:i',$v['add_time']);
	    $v['updateTime'] = date('Y-m-d H:i',$v['update_time']);
	    return $v ;
	}
}