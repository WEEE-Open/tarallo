<?php 



namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\ItemWithCode;

class DonationsDAO extends DAO
{
    public function newDonation($name, $location, $notes, $date, $itemsList, $tasks)
    {
        $statement = $this->getPDO()->prepare("INSERT INTO Donations(DonationName,Location,Date,Notes,IsCompleted) VALUES(:name,:location,:date,:notes,0)");

        try {
            //bind parametres
            $statement->bindParam(':name',$name);
            $statement->bindParam(':location',$location);
            $statement->bindParam(':date',$date);
            $statement->bindParam(':notes',$notes);

            $success = $statement->execute();

            return $this->getPDO()->lastInsertId();
        } finally{
            $statement->closeCursor();
        }
    }
}