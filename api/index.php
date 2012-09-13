<?php

// Load the framework
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

// New framework app.
$app = new \Slim\Slim(array('debug' => true));

// Create the routes.
$app->get('/posts/:timeframe/:id', 'getPosts');
$app->post('/posts/new', 	'addPost');

// Run the app.
$app->run();

// Returns a limited amount of posts based on id.
function getPosts($timeframe, $id) {
	try {
		// Make sure that the ID we get really is an int.
		if(!check_int($id)) {
			throw new Exception('id should be integer!');
		}

		// Use the right SQL for the job + make sure no sql can be injected.
		if($timeframe == "after") {
			$sql = "SELECT * FROM posts WHERE id > :id ORDER BY id ASC LIMIT 10";
		} elseif($timeframe == "before") {
			$sql = "SELECT * FROM posts WHERE id < :id ORDER BY id DESC LIMIT 10";
		} else {
			throw new Exception('timeframe should be "before" or "after"!');
		}

		// Fetch objects from query;
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindValue("id", $id, PDO::PARAM_INT);
		$stmt->execute();
		$posts = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;

		// Output json.
		echo json_encode($posts);
	} catch(Exception $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

// Add a post.
function addPost() {
	// get the request to get the body & parse the json.
	$request = \Slim\Slim::getInstance()->request();
	$post = json_decode($request->getBody());
	$sql = "INSERT INTO posts (poster, body, gravatar) VALUES (:poster, :body, :gravatar)";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		// Polish the data for posting. (substr to strip outer quotes)
		$poster = substr($db->quote($post->poster), 1, -1);
		$body = str_replace('\n', "<br>", substr($db->quote($post->body), 1, -1));
		$gravatar = md5($post->gravatar);

		// insert the data into the PDOStatement.
		$stmt->bindParam("poster", $poster);
		$stmt->bindParam("body", $body);
		$stmt->bindParam("gravatar", $gravatar);	
		$stmt->execute();
		$db = null;

		// first make sure that firstid == int
		if(!check_int($post->firstid)) {
			throw new Exception('firstid must be int');
		}
		// after that return all posts after the first post on the client
		getPosts('after', $post->firstid);
	} catch(Exception $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

// Function to check if var is an int in some way (including 0);
function check_int($var) {
	if (strval(intval($var)) == strval($var)) { 
		return true;
	} else {
		return false;
	}
}

// Function to initiate DB connection
function getConnection() {
	$dbhost = "127.0.0.1";
	$dbuser = "root";
	$dbpass = "gentle66";
	$dbname = "infinity";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbh;
}
