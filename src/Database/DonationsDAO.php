<?php 



namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\ItemWithCode;

class DonationsDAO extends DAO
{
    public function newDonation($name, $location, $notes, $date, $itemsList, $tasks)
    {
        $statement = $this->getPDO()->prepare("INSERT INTO Donations(Name,Location,Date,Notes,IsCompleted) VALUES(:name,:location,:date,:notes,0)");
        $parsedDate = date('Y-m-d',$date);

        try {
            //bind parametres
            $statement->bindParam(':name',$name);
            $statement->bindParam(':location',$location);
            $statement->bindParam(':date', $parsedDate);
            $statement->bindParam(':notes',$notes);

            $success = $statement->execute();

            $donationId = $this->getPDO()->lastInsertId();

            $itemsTypes = $this->database->itemDAO()->getTypesForItemCodes($itemsList);

            //inverting the items array and grouping them by type

            $grouped = array();
            foreach($itemsTypes as $id => $type) {
                if (isset($grouped[$type])) {
                    $grouped[$type][] = $id;
                } else {
                    $grouped[$type] = array($id);
                }
            }

            foreach($itemsList as $item) {
                $statement = $this->getPDO()->prepare("INSERT INTO DonationItem(Donation,Code) VALUES(:donationId,:code)");
                $statement->bindParam(':donationId',$donationId);
                $statement->bindParam(':code',$item);

                $success = $statement->execute();
            }

            foreach($grouped as $type => $items) {
                $taskIds = [];
                if (isset($tasks[$type]) && count($tasks[$type]) > 0) {
                    $i = 0;
                    foreach($tasks[$type] as $task) {
                        $statement = $this->getPDO()->prepare("INSERT INTO DonationTasks(DonationId, `Index`, Title, ItemType) VALUES($donationId, $i, :task, :itemType)");
                        $statement-> bindParam(':task', $task);
                        $statement-> bindParam(':itemType', $type);
                        $success = $statement->execute();
                        $i++;
                        
                        array_push($taskIds, $this->getPDO()->lastInsertId());
                    }
                } else {
                    $statement = $this->getPDO()->prepare("INSERT INTO DonationTasks(DonationId, `Index`, Title, ItemType) VALUES($donationId, -1, :task, :itemType)");
                    $statement-> bindParam(':task', $task);
                    $statement-> bindParam(':itemType', $type);
                    $success = $statement->execute();

                    array_push($taskIds, $this->getPDO()->lastInsertId());
                }

                foreach($taskIds as $taskId) {
                    foreach($items as $item) {
                        $statement = $this->getPDO()->prepare("INSERT INTO DonationTasksProgress(DonationId, TaskId, ItemCode, Completed) VALUES($donationId, $taskId, :item, 0)");
                        $statement-> bindParam(':item', $item);
                        $success = $statement->execute();
                    }
                }
            }

            return $donationId;
        } finally{
            $statement->closeCursor();
        }
    }
}