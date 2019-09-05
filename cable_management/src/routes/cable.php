<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//$app = new \Slim\App;

$app->get('/api/cables', function (Request $request, Response $response, array $args) {
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
                    cable.idBoxDefault = box.idBox
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

$app->get('/api/cable/{id}', function (Request $request, Response $response, array $args) {
    $id = $request->getAttribute('id');

    $sql = "SELECT  cable.idCable,
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
                    idCable = '$id'";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $cable = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode($cable);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});
$app->get('/api/cable/', function (Request $request, Response $response, array $args) {
    $params = $request->getQueryParams();

    $type = $params['type'];
    $conn_A = $params['conn_A'];
    $conn_B = $params['conn_B'];
    $length_min = $params['length_min'];
    $length_max = $params['length_max'];

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
                    cable.type LIKE '%$type%' AND
                    (( c.name LIKE '%$conn_A%' AND v.name LIKE '%$conn_B%') OR
                    ( v.name LIKE '%$conn_A%' AND c.name LIKE '%$conn_B%'))
                    AND cable.length between $length_min and $length_max  ";

    $sql = $sql . "ORDER BY cable.id ASC";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $cable = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode($cable);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});
$app->get('/api/cables/maxlength', function (Request $request, Response $response, array $args) {
    $sql = "SELECT MAX(length) as max FROM cable";
    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $len = $stmt->fetchObject();
        $db = null;

        echo json_encode($len);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});

// add costumer
$app->post('/api/cable/add', function (Request $request, Response $response, array $args) {
    $type   = $request->getParam('type');
    $conn_A = $request->getParam('conn_A');
    $conn_B = $request->getParam('conn_B');
    $length = $request->getParam('length');
    $price  = $request->getParam('price');
    $box = $request->getParam('box');
    $notes = $request->getParam('notes');

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $conn_A_ID = getConnectorID($conn_A);

        $conn_B_ID = getConnectorID($conn_B);

        $sql = "SELECT idBox FROM box WHERE name='$box'";
        $stmt = $db->query($sql);
        $box_ID = $stmt->fetch()['idBox'];

        switch ($type) {
            case 'audio':
                $cableID = 'A ';
                break;
            case 'video':
                $cableID = 'V ';
                break;
            case 'data':
                $cableID = 'D ';
                break;
            case 'light':
                $cableID = 'L ';
                break;
            case 'power':
                $cableID = 'P ';
                break;
        }
        $cableID = $cableID . lastCableID();

        $sql = "INSERT INTO Cable 
                (idCable, type, conn_A, conn_B, length, price, idBoxDefault,notes)
                VALUES 
                (:idCable, :type, :conn_A, :conn_B, :length, :price, :idBoxDefault,:notes)";

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':idCable', $cableID);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':conn_A', $conn_A_ID);
        $stmt->bindParam(':conn_B', $conn_B_ID);
        $stmt->bindParam(':length', $length);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':idBoxDefault', $box_ID);
        $stmt->bindParam(':notes', $notes);

        $stmt->execute();

        echo json_encode('{"notice":{"text":' . $cableID . '}}');
        $db = null;
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
        return;
    }
});

$app->get('/api/cables/lastid', function (Request $request, Response $response, array $args) {
    $id = lastCableID();
    echo '{"id":"' . $id . '"}';
    return;
});

function lastCableID()
{

    $db = new db();
    // connect
    $db = $db->connect();
    $sql = "SELECT id,idCable FROM cable ORDER BY id DESC LIMIT 1";
    $stmt = $db->query($sql);

    if ($stmt->rowCount() == 0) {
        return '001';
    } else {

        $complete_id = $stmt->fetch()['idCable'];
        $id = explode(' ', $complete_id);

        // return $id[1];
        return fillZeros($id[1] + 1);
    }
}

function fillZeros($str)
{
    while (strlen($str) < 3) {
        $str = '0' . $str;
    }
    return $str;
}

function getConnectorID($conName)
{
    //echo $conName;
    $db = new db();
    // connect
    $db = $db->connect();
    $sql = "SELECT idCon FROM connector WHERE name='$conName'";
    $stmt = $db->query($sql);
    return $stmt->fetch()['idCon'];
}

$app->put('/api/cable/update/{id}', function (Request $request, Response $response, array $args) {
    $oldId = $request->getAttribute('id');

    $idCable = $request->getParam('idCable');
    $type    = $request->getParam('type');
    $conn_A  = $request->getParam('conn_A');
    $conn_B  = $request->getParam('conn_B');
    $length  = $request->getParam('length');
    $price   = $request->getParam('price');
    $box     = $request->getParam('box');
    $notes     = $request->getParam('notes');


    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $conn_A_ID = getConnectorID($conn_A);
        $conn_B_ID = getConnectorID($conn_B);

        $sql = "SELECT idBox FROM box WHERE name='$box'";
        $stmt = $db->query($sql);
        $box_ID = $stmt->fetch()['idBox'];

        $sql = "SELECT id FROM cable WHERE idCable='$oldId'";
        $stmt = $db->query($sql);
        $id = $stmt->fetch()['id'];

        $sql = "UPDATE cable SET
                    id = :id,
                    idCable = :idCable,
                    type=:type,
                    conn_A=:conn_A,
                    conn_B=:conn_B,
                    length=:length,
                    price=:price,
                    idBoxDefault=:idBoxDefault,
                    notes=:notes
            WHERE idCable = '$oldId'";

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':idCable', $idCable);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':conn_A', $conn_A_ID);
        $stmt->bindParam(':conn_B', $conn_B_ID);
        $stmt->bindParam(':length', $length);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':idBoxDefault', $box_ID);
        $stmt->bindParam(':notes', $notes);


        $stmt->execute();

        echo '{"notice":{"text":"cable Updated"}}';
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});

$app->delete('/api/cable/delete/{id}', function (Request $request, Response $response, array $args) {
    $id = $request->getAttribute('id');

    $sql = "DELETE FROM cable WHERE idCable = '$id'";

    try {
        //create DB Obj
        $db = new db();
        // connect
        $db = $db->connect();

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $db = null;

        echo '{"notice":{"text":"cable Deleted"}}';
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }
});
