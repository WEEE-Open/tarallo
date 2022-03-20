<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\ItemWithCode;

class DonationsDAO extends DAO
{

    /**
     * Get all donations stored in DB
     * @return array
     */
    public function getAllDonations(): array
    {
        $query = "SELECT * FROM Donations ORDER BY IsCompleted , Date";
        $statement = $this->getPDO()->prepare($query);
        $donations = [];
        try
        {
            $success = $statement->execute();
            assert($success, 'Donations');
            while( $row = $statement->fetch(\PDO::FETCH_ASSOC) )
            {
                $donations[] = $row;
            }
        } finally {
            $statement->closeCursor();
        }
        return $donations;
    }

    /**
     * get a specific donation
     * @param Int $id_donation id of the donation
     */
    public function getDonation(Int $id_donation): array
    {
        $query = "SELECT * FROM Donations WHERE Donation = :id ";

        $statement = $this->getPDO()->prepare($query);
        $donation = [];
        try {
            //bind parametres
            $statement->bindParam(':id',$id_donation);
            $success = $statement->execute();
            assert($success, 'Donation');
            while( $row = $statement->fetch(\PDO::FETCH_ASSOC) )
            {
                $donation = $row;
            }
        } finally{
            $statement->closeCursor();
        }
        return $donation;
    }

    /**
     * Create a new Record in Donation Table
     *
     * @param String $donation_name
     * @param String|null $date_when_donation_will_do
     * @param String $location
     * @param String $donation_note
     * @param bool $donation_status
     * @param Int $reference_user
     */
    public function newDonation(String $donation_name, String $date_when_donation_will_do = null , String $location, String $donation_note, Bool $donation_status, String $reference_user)
    {
        //sanitize data
        $donationNameSanitized = strip_tags($donation_name);
        $dateWhenDonationWillDoSanitized = $date_when_donation_will_do ? preg_replace("([^0-9/])", "", $date_when_donation_will_do) : null; // deleting every character except number and /
        $locationSanitized = strip_tags( $location );
        $donationNoteSanitized = strip_tags($donation_note);
        $referenceUserSanitized = strip_tags($reference_user);
        $donationStatusSanitized = $donation_status ? 1 : 0;

        $query = "INSERT INTO Donations(DonationName,Location,Date,ReferenceUser,Note,IsCompleted) VALUES(:name,:location,:date,:refUser,:note,:isCompleted)";

        $statement = $this->getPDO()->prepare($query);

        try {
            //bind parametres
            $statement->bindParam(':name',$donationNameSanitized);
            $statement->bindParam(':location',$locationSanitized);
            $statement->bindParam(':date',$dateWhenDonationWillDoSanitized);
            $statement->bindParam(':refUser',$referenceUserSanitized);
            $statement->bindParam(':note',$donationNoteSanitized);
            $statement->bindParam(':isCompleted',$donationStatusSanitized);

            $success = $statement->execute();
            assert($success, 'New Record Created');
        } finally{
            $statement->closeCursor();
        }
    }

    /**
     * Delete a donation through id
     * @param Int $id Id of donation
     */
    function deleteDonation(Int $id)
    {
        $query = "DELETE FROM Donations WHERE Donation = :id";

        $statement = $this->getPDO()->prepare($query);
        try {
            //bind parametres
            $statement->bindParam(':id',$id);
            $success = $statement->execute();
            assert($success, 'Donation Deleted');

        } finally{
            $statement->closeCursor();
        }
    }

    /**
     * Updating an exiting donation
     * @param array $donation
     */
    function updateDonation(Array $donation)
    {
        $query = "";

        $statement = $this->getPDO()->prepare($query);
        try {
            //bind parametres
            $statement->bindParam();
            $success = $statement->execute();

        } finally{
            $statement->closeCursor();
        }
    }

    /**
     * returns the code of items related to the donation
     * @param Int $id_donation
     * @return array
     */
    function itemsOfDonation(Int $id_donation) : array
    {
        $query = "SELECT Code FROM ItemDonation WHERE Donation = :id ";

        $statement = $this->getPDO()->prepare($query);
        $itemsCode = [];
        try {
            //bind parametres
            $statement->bindParam(':id',$id_donation);
            $success = $statement->execute();
            assert($success, 'Items found');
            while( $row = $statement->fetch(\PDO::FETCH_ASSOC) )
            {
                $itemsCode[] = new ItemCode($row['Code']);
            }
        } finally{
            $statement->closeCursor();
        }
        return $itemsCode;
    }



}