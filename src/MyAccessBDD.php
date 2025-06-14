<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */	
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){  
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "commandedocument" :
                return $this->selectCommandeDocument($champs);
            case "abonnement" :
                return $this->selectAbonnementRevue($champs);
            case "commandemax":
                return $this->selectMaxIdCom();
            case "utilisateur":
                return $this->selectUtilisateur($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }	
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs) : ?int{
        switch($table){
            case "commandedocument" :
                return $this->insertCommandeDocument($champs);
            case "abonnement" :
                return $this->insertAbonnement($champs);
            default:                    
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);	
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : ?int{
        switch($table){
            case "commandedocument" :
                return $this->UpdateCommDoc($id, $champs);
            case "abonnement" :
                return $this->UpdateAbonnement($id, $champs);
            default:
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }
    }  
    
    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */	
    protected function traitementDelete(string $table, ?array $champs) : ?int{
        switch($table){
            case "commandedocument" :
                return $this->deleteComDoc($champs);
            case "abonnement" :
                return $this->deleteAbonnement($champs);
            default:             
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);	
        }
    }	    
        
    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);  
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);	          
            return $this->conn->queryBDD($requete, $champs);
        }
    }	

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);				
        $champs["id"] = $id;
        $requete .= " where id=:id;";		
        return $this->conn->updateBDD($requete, $champs);	        
    }
    
    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);   
        return $this->conn->updateBDD($requete, $champs);	        
    }
 
    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";		
        return $this->conn->queryBDD($requete);	    
    }
    
    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";		
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd() : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";	
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs 
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

private function selectCommandeDocument(?array $champs): ?array {
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('idLivreDvd', $champs)){
            return null;
        }
        $champNecessaire['idLivreDvd'] = $champs['idLivreDvd'];
        $requete = "SELECT c.id AS Id, c.dateCommande, c.montant, ";
        $requete .= "cd.id AS Id, cd.nbExemplaire, cd.idLivreDvd, cd.idSuivi, s.etapeSuivi ";
        $requete .= "FROM commandedocument cd ";
        $requete .= "JOIN commande c ON cd.id = c.id ";
        $requete .= "JOIN suivi s ON cd.idSuivi = s.idSuivi ";
        $requete .= "WHERE cd.idLivreDvd = :idLivreDvd ";
        $requete .= "ORDER BY c.dateCommande DESC";

        return $this->conn->queryBDD($requete, $champNecessaire);
}


private function selectAbonnementRevue(?array $champs): ?array {
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('idRevue', $champs)){
            return null;
        }
        $champNecessaire['idRevue'] = $champs['idRevue'];
        $requete = "SELECT c.id AS Id, c.dateCommande, c.montant, ";
        $requete .= "a.id AS Id, a.dateFinAbonnement, a.idRevue ";
        $requete .= "FROM abonnement a ";
        $requete .= "JOIN commande c ON a.id = c.id ";
        $requete .= "WHERE a.idRevue = :idRevue ";
        $requete .= "ORDER BY c.dateCommande DESC";

        return $this->conn->queryBDD($requete, $champNecessaire);
}

       private function selectMaxIdCom()
    {
        $requete = "SELECT MAX(id) AS id FROM commande";
        return $this->conn->queryBDD($requete);
    }
    
    private function selectUtilisateur($champs){

        $param = [
            'login' => $champs['login']
        ];
        $requete = "SELECT u.id AS Id, u.login, u.password, ";
        $requete .= "u.idService, s.libelle as service ";
        $requete .= "FROM utilisateur u ";
        $requete .= "JOIN service s ON u.idService = s.id ";
        $requete .= "WHERE u.login = :login ";
        return $this->conn->queryBDD($requete, $param);
    }

     
     /**
     * Ajout de l'entitée composée commandeDocument dans la bdd
     *
     * @param [type] $champs nom et valeur de chaque champs de la ligne
     * @return true si l'ajout a fonctionné
     */
    private function insertCommandeDocument($champs)
    {
        $champsCommande = [ "id" => $champs["Id"], "dateCommande" => $champs["DateCommande"],
            "montant" => $champs["Montant"]];
        $champsCommandeDocument = [ "id" => $champs["Id"], "nbExemplaire" => $champs["NbExemplaire"],
                "idLivreDvd" => $champs["IdLivreDvd"], "idSuivi" => $champs["IdSuivi"]];
        $result = $this->insertOneTupleOneTable("commande", $champsCommande);
        if ($result == null || $result == false){
            return null;
        }
        return  $this->insertOneTupleOneTable("commandedocument", $champsCommandeDocument);
    }
    
      /**
     * Ajout de l'entitée composée commandeabonnement dans la bdd
     *
     * @param [type] $champs nom et valeur de chaque champs de la ligne
     * @return true si l'ajout a fonctionné
     */
    private function insertAbonnement($champs)
    {
        $champsCommande = [ "id" => $champs["Id"], "dateCommande" => $champs["DateCommande"],
            "montant" => $champs["Montant"]];
        $champsAbonnement = [ "id" => $champs['Id'], "dateFinAbonnement" => $champs["DateFinAbonnement"],
                "idRevue" => $champs["IdRevue"]];
        $result = $this->insertOneTupleOneTable("commande", $champsCommande);
        if ($result == null || $result == false){
            return null;
        }
        return  $this->insertOneTupleOneTable("abonnement", $champsAbonnement);
    }
    
    private function UpdateCommDoc($id, $champs)
    {

         $champsCommande = [ "id" => $id, "dateCommande" => $champs["DateCommande"],
            "montant" => $champs["Montant"]];
        $champsCommandeDocument = [ "id" => $id, "nbExemplaire" => $champs["NbExemplaire"],
                "idLivreDvd" => $champs["IdLivreDvd"], "idSuivi" => $champs["IdSuivi"]];
        $result = $this->updateOneTupleOneTable("commandedocument", $id, $champsCommandeDocument);
        if ($result == null || $result == false){
            return null;
        }
        return $this->updateOneTupleOneTable("commande", $id, $champsCommande);  
    }
    
     private function UpdateAbonnement($id, $champs)
    {

         $champsCommande = [ "id" => $id, "dateCommande" => $champs["DateCommande"],
            "montant" => $champs["Montant"]];
        $champsAbonnement = [ "id" => $id, "dateFinAbonnement" => $champs["DateFinAbonnement"],
                "idRevue" => $champs["IdRevue"]];
        $result = $this->updateOneTupleOneTable("abonnement", $id, $champsAbonnement);
        if ($result == null || $result == false){
            return null;
        }
        return $this->updateOneTupleOneTable("commande", $id, $champsCommande);  
    }
    
    
    
    
    private function deleteComDoc($champs)
    {
         if(empty($champs)){
            return false;
        }
        
        $champsCommande = [ "id" => $champs["Id"]];
        $champsCommandeDocument = [ "id" => $champs["Id"]];
         $result = $this->deleteTuplesOneTable("commandedocument", $champsCommandeDocument);
        if ($result == null || $result == false){
            return false;
        }
        return $this->deleteTuplesOneTable("commande", $champsCommande);
    }
    
    private function deleteAbonnement($champs)
    {
         if(empty($champs)){
            return false;
        }
        
        $champsCommande = [ "id" => $champs["Id"]];
        $champsAbonnement = [ "id" => $champs["Id"]];
         $result = $this->deleteTuplesOneTable("abonnement", $champsAbonnement);
        if ($result == null || $result == false){
            return false;
        }
        return $this->deleteTuplesOneTable("commande", $champsCommande);
    }

}
