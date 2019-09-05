<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// $app = new \Slim\App;

$app->get('/api/boxes', function (Request $request, Response $response, array $args) {
    $sql = "SELECT idBox,name,box.notes,count(cable.id) as n_cables FROM Box LEFT JOIN cable ON box.idBox=cable.idBoxDefault GROUP BY idBox";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $boxes = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode($boxes);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});

// GET CABLES BY BOX ID
$app->get('/api/box/{id}/cables', function (Request $request, Response $response, array $args) {

    $id = $request->getAttribute('id');

    $sql = "SELECT  cable.id,
                    cable.idCable,
                    cable.type,
                    c.name as connector_A,
                    v.name as connector_B,
                    cable.length,
                    cable.price,
                    box.name as box,
                    cable.notes
            FROM    cable,connector as c, connector as v,box 
            WHERE   cable.conn_A = c.idCon AND
                    cable.conn_B = v.idCon AND
                    cable.idBoxDefault = box.idBox AND
                    box.idBox = $id
            ORDER BY cable.id ASC";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $cables = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode($cables);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});
$app->get('/api/box/cables', function (Request $request, Response $response, array $args) {

    $sql = "SELECT  *
            FROM    Box";
    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);

        $boxes = $stmt->fetchAll(PDO::FETCH_OBJ);

        $what = "";

        foreach ($boxes as $box) {
            $sql2 = "SELECT 
            cable.idCable,
            cable.type,
            c.name as connector_A,
            v.name as connector_B,
            cable.length,
            cable.price
    FROM    cable,connector as c, connector as v,box 
    WHERE   cable.conn_A = c.idCon AND
            cable.conn_B = v.idCon AND
            cable.idBoxDefault = box.idBox AND 
            cable.idBoxDefault = $box->idBox";
            $stmt2 = $db->query($sql2);
            $box->cables = $stmt2->fetchAll(PDO::FETCH_OBJ);
            $what = $sql2;
        }

        $db = null;

        echo json_encode($boxes);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});

$app->get('/api/box/{id}', function (Request $request, Response $response, array $args) {
    $id = $request->getAttribute('id');

    $sql = "SELECT * FROM box WHERE idBox = $id";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $box = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode($box);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});
// add costumer
$app->post('/api/box/add', function (Request $request, Response $response, array $args) {
    $name = $request->getParam('name');
    $notes = $request->getParam('notes');

    $sql = "SELECT * FROM box WHERE name='$name'";
    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);

        if ($stmt->rowCount() == 0) {
            $sql = "INSERT INTO box (name,notes) VALUES (:name,:notes)";

            $stmt = $db->prepare($sql);

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':notes', $notes);

            $stmt->execute();

            echo '{"notice":{"text":"box Added"}}';
            $db = null;
        } else {
            echo '{"error": {"text": "Box Name must be unique!","rowCount":' . $stmt->rowCount() . '}}';
            return;
        }
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
        return;
    }
});

$app->put('/api/box/update/{id}', function (Request $request, Response $response, array $args) {
    $id = $request->getAttribute('id');
    $name = $request->getParam('name');
    $notes = $request->getParam('notes');

    $sql = "UPDATE box SET
                name = :name,
                notes = :notes
            WHERE idBox = $id";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':notes', $notes);
        $stmt->execute();

        echo '{"notice":{"text":"box Updated"}}';
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});

$app->delete('/api/box/delete/{id}', function (Request $request, Response $response, array $args) {
    $id = $request->getAttribute('id');

    $sql = "UPDATE cable SET idBoxDefault='0' WHERE idBoxDefault = '$id'";

    try {

        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->prepare($sql);
        $stmt->execute();

        $sql = "DELETE FROM box WHERE idBox = $id";
        $stmt = $db->prepare($sql);
        $stmt->execute();

        $db = null;

        echo '{"notice":{"text":"box Deleted"}}';
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});
