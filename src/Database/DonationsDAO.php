<?php

namespace WEEEOpen\Tarallo\Database;

class DonationsDAO extends DAO
{

    /**
     * Get all donations stored in DB
     * @return array
     */
    public function getAllDonations(): array
    {
        $query = "SELECT * FROM Donations";
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


}