<?php

// user model
$user_schema = (new GDS\Schema('User'))
   ->addString('name')
   ->addString('username')
   ->addString('email')
   ->addString('password')
   ->addString('bio')
   ->addDatetime('registered_on')
   ->addDatetime('last_login_on')
   ->addBoolean('is_active');
   
$user_store = new GDS\Store($user_schema);

function get_user($username){
	global $user_schema, $user_store;
	
	$user = $user_store->fetchOne("SELECT * FROM User WHERE username = '$username'");
	var_dump($user);
	return $user;
}

function create_user(){
	// Build a new entity
	$obj_user = new GDS\Entity();
	$obj_user->title = 'Romeo and Juliet';
	$obj_book->author = 'William Shakespeare';
	$obj_book->isbn = '1840224339';

	// Write it to Datastore
	$obj_store = new GDS\Store('Book');
	$obj_store->upsert($obj_book);
}





use google\appengine\api\mail\Message;


/*try {
    $message = new Message();
    $message->setSender('admin@test-php-101.appspotmail.com');
    $message->addTo('md.sabuj.sarker+appengine_test@gmail.com');
    $message->setSubject('Example email');
    $message->setTextBody('Hello, world!');
    $message->send();
    echo 'Mail Sent';
//} catch (InvalidArgumentException $e) {
   // echo 'There was an error';
}*/