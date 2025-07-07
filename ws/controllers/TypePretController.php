<?php

class TypePretController {
    public static function getAll() {
        $stmt = getDB()->query("SELECT * FROM type_pret");
        Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function getById($id) {
        $stmt = getDB()->prepare("SELECT * FROM type_pret WHERE id = ?");
        $stmt->execute([$id]);
        Flight::json($stmt->fetch(PDO::FETCH_ASSOC));
    }

    public static function create() {
        $data = Flight::request()->data;
        $stmt = getDB()->prepare("INSERT INTO type_pret (nom, taux, duree_mois) VALUES (?, ?, ?)");
        $stmt->execute([$data['nom'], $data['taux'], $data['duree_mois']]);
        Flight::json(['success' => true]);
    }

    public static function update($id) {
        parse_str(file_get_contents("php://input"), $data);
        $stmt = getDB()->prepare("UPDATE type_pret SET nom = ?, taux = ?, duree_mois = ? WHERE id = ?");
        $stmt->execute([$data['nom'], $data['taux'], $data['duree_mois'], $id]);
        Flight::json(['success' => true]);
    }

    public static function delete($id) {
        $stmt = getDB()->prepare("DELETE FROM type_pret WHERE id = ?");
        $stmt->execute([$id]);
        Flight::json(['success' => true]);
    }
}