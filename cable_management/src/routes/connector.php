<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// $app = new \Slim\App;

$app->get('/api/connectors', function (Request $request, Response $response, array $args) {
    $sql = "SELECT * FROM connector";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $connectors = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode($connectors);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});

$app->get('/api/connector/{id}', function (Request $request, Response $response, array $args) {
    $id = $request->getAttribute('id');

    $sql = "SELECT * FROM connector WHERE idCon = $id";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $connector = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode($connector);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});



$app->get('/api/connector/name/{name}', function (Request $request, Response $response, array $args) {
    $name = $request->getAttribute('name');

    $sql = "SELECT * FROM connector WHERE name LIKE '%$name%'";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $connector = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode($connector);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});
// add costumer
$app->post('/api/connector/add', function (Request $request, Response $response, array $args) {
    $name = $request->getParam('name');

    $sql = "SELECT * FROM connector WHERE name='$name'";
    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);

        if ($stmt->rowCount() == 0) {
            $sql = "INSERT INTO connector (name) VALUES (:name)";

            $stmt = $db->prepare($sql);

            $stmt->bindParam(':name', $name);

            $stmt->execute();

            echo '{"notice":{"text":"connector Added"}}';
            $db = null;
        } else {
            echo '{"error": {"text": "Already Existing","rowCount":' . $stmt->rowCount() . '}}';
            return;
        }
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
        return;
    }
});

$app->put('/api/connector/update/{id}', function (Request $request, Response $response, array $args) {
    $id = $request->getAttribute('id');
    $name = $request->getParam('name');

    $sql = "UPDATE connector SET
                name = :name
            WHERE idCon = $id";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':name', $name);

        $stmt->execute();

        echo '{"notice":{"text":"connector Updated"}}';
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});

$app->delete('/api/connector/delete/{id}', function (Request $request, Response $response, array $args) {
    $id = $request->getAttribute('id');

    $sql = "DELETE FROM connector WHERE idCon = $id";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $db = null;

        echo '{"notice":{"text":"connector Deleted"}}';
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});
