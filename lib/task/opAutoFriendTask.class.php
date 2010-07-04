<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

class opAutoFriendTask extends sfBaseTask
{
  protected function configure()
  {
    $this->namespace        = 'tjm';
    $this->name             = 'AutoFriend';
    $this->briefDescription = 'This plugin makes friend link automatically.';
    $this->detailedDescription = <<<EOF

  [./symfony tjm-AutoFriend]
EOF;

    $this->addOption('target', null, sfCommandOption::PARAMETER_OPTIONAL, 'target', null);

  }

  protected function execute($arguments = array(), $options = array())
  {
    if(isset($options['target'])){
      $this->autoFriendWithId($options['target']);
    }else{
      $this->autoFriendAll();
    }
  }
  private function autoFriendAll(){
    $databaseManager = new sfDatabaseManager($this->configuration);

    //最初に全フレンドリンクを削除
    Doctrine_Query::create()->delete('MemberRelationship s')->execute();
    $conn = $databaseManager->getDatabase(array_shift($databaseManager->getNames()))->getConnection();
    $stmt = $conn->prepare('insert into member_relationship (member_id_to,member_id_from,is_friend,is_friend_pre) SELECT m1.id as member_id_to ,m2.id as member_id_from ,1 as is_friend , 0 as is_friend_pre FROM member as m1,member as m2 WHERE m1.id != m2.id');
    $stmt->execute();
  }
  private function autoFriendWithId($target_id = null){
    if(!$target_id){
      return;
    }
    $databaseManager = new sfDatabaseManager($this->configuration);
    $conn = $databaseManager->getDatabase(array_shift($databaseManager->getNames()))->getConnection();
    //ターゲットメンバーの既存フレンドリンクを削除
    Doctrine_Query::create()->delete()->from('MemberRelationship')
      ->where('member_id_to = ?',$target_id)->orWhere('member_id_from = ?',$target_id)->execute();

    $stmt = $conn->prepare('insert into member_relationship (member_id_to,member_id_from,is_friend,is_friend_pre) select id as member_id_to , ? as member_id_from, 1 as is_friend, 0 as is_friend_pre from member where id != ? UNION select ? as member_id_to , id as member_id_from , 1 as is_friend, 0 as is_friend_pre from member where id !=?;');
    $stmt->execute(array($target_id,$target_id,$target_id,$target_id));
  }
}
