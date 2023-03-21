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

	public function donationExists($id)
	{
		$statement = $this->getPDO()->prepare("SELECT COUNT(*) as `Exists` FROM Donations WHERE Id=:id LIMIT 1");
		try {
			$parsedId = intval($id) ?? 0;
			$statement->bindParam(':id', $parsedId);
			$success = $statement->execute();
			$res = $statement->fetchAll(\PDO::FETCH_ASSOC);
			return boolval($res[0]["Exists"]);
		} finally {
			$statement->closeCursor();
		}
	}

	public function listDonations()
	{
		$statement = $this->getPDO()->prepare("SELECT d.Id as id, d.Name as name, d.Location as location, d.Date as date, d.IsCompleted as isCompleted,
(SELECT COUNT(*) FROM DonationItem di WHERE di.Donation = d.Id) AS totalItems,
(SELECT COUNT(*) FROM DonationTasksProgress dt WHERE dt.DonationId = d.Id) AS totalTasks,
(SELECT COUNT(*) FROM DonationTasksProgress dtp WHERE dtp.DonationId = d.Id AND dtp.Completed = 1) AS completedTasks
FROM Donations d
LEFT JOIN DonationTasks dt ON dt.DonationId = d.Id
LEFT JOIN DonationTasksProgress dtp ON dtp.DonationId = d.Id AND dtp.TaskId = dt.Id
LEFT JOIN DonationItem di ON di.Donation = d.Id
GROUP BY d.Id");
		try {
			$success = $statement->execute();
			$donations = $statement->fetchAll(\PDO::FETCH_ASSOC);
			foreach($donations as $d) {
				$d["isCompleted"] = boolval($d["isCompleted"]);
			}
			return $donations;
		} finally {
			$statement->closeCursor();
		}
	}

	public function getDonation($id)
	{
		$id = intval($id);
		$statement = $this->getPDO()->prepare("SELECT Id AS id, Name AS name, Location AS location, Date AS date, Notes AS notes, IsCompleted AS isCompleted FROM Donations WHERE Id = :id");
		try {
			$statement->bindParam(':id',$id);
			$success = $statement->execute();
			$donation = $statement->fetchAll(\PDO::FETCH_ASSOC);
			if (empty($donation)) {
				return false;
			} else {
				$donation = $donation[0];

				$donation["isCompleted"] = boolval($donation["isCompleted"]);

				$statement = $this->getPDO()->prepare("SELECT Title, ItemType, `Index` FROM DonationTasks WHERE DonationId = :id ORDER BY `Index` ASC");
				$statement->bindParam(':id',$id);

				$success = $statement->execute();
				$tasksList = $statement->fetchAll(\PDO::FETCH_ASSOC);

				$tasks = array();
				foreach($tasksList as $t) {
					if ($t["Index"] === -1) {
						$tasks[$t["ItemType"]] = $t["Title"];
					} else {
						if (!is_bool($tasks[$t["ItemType"]] ?? '')) {
							if (isset($tasks[$t["ItemType"]])) {
								$tasks[$t["ItemType"]][] = $t["Title"];
							} else {
								$tasks[$t["ItemType"]] = array($t["Title"]);
							}
						}
					}
				}

				$donation["tasks"] = $tasks;

				$statement = $this->getPDO()->prepare("SELECT Code FROM DonationItem WHERE Donation = :id");
				$statement->bindParam(':id',$id);

				$success = $statement->execute();
				$itemsListAssoc = $statement->fetchAll(\PDO::FETCH_ASSOC);

				$itemsList = [];
				foreach($itemsListAssoc as $i) {
					array_push($itemsList, $i["Code"]);
				}

				$donation["itemsType"] = $this->database->itemDAO()->getTypesForItemCodes($itemsList);

				$statement = $this->getPDO()->prepare("SELECT ItemCode, `Index`, Completed FROM DonationTasksProgress d LEFT JOIN DonationTasks dt ON dt.DonationId = d.DonationId AND d.TaskId = dt.Id WHERE d.DonationId = :id ORDER BY `Index` ASC");
				$statement->bindParam(':id',$id);

				$success = $statement->execute();
				$tasksProgressList = $statement->fetchAll(\PDO::FETCH_ASSOC);

				$tasksProgress = [];
				$countCompletedTasks = 0;

				foreach($tasksProgressList as $task) {
					if (boolval($task["Completed"])) $countCompletedTasks++;
					if ($task["Index"] === -1) {
						$tasksProgress[$task["ItemCode"]] = boolval($task["Completed"]);
					} else {
						if (!is_bool($tasksProgress[$task["ItemCode"]] ?? '')) { // Prevents a possible bug
							if (is_array($tasksProgress[$task["ItemCode"]] ?? '')) {
								array_push($tasksProgress[$task["ItemCode"]], boolval($task["Completed"]));
							} else {
								$tasksProgress[$task["ItemCode"]] = [boolval($task["Completed"])];
							}
						}
					}
				}

				$donation["tasksProgress"] = $tasksProgress;
				$donation["totalTasks"] = count($tasksProgressList);
				if ($donation["totalTasks"] === 0) {
					$donation["progress"] = 0;
				} else {
					$donation["progress"] = round($countCompletedTasks/$donation["totalTasks"]*100, 0, PHP_ROUND_HALF_DOWN);
				}

				return $donation;

			}
		} finally {
			$statement->closeCursor();
		}
	}

	public function updateTasksProgress($id, $tasks)
	{
		foreach($tasks as $task => $progress) {
			$statement = $this->getPDO()->prepare("UPDATE DonationTasksProgress dtp JOIN DonationTasks dt ON dtp.TaskId = dt.Id SET dtp.Completed = :completed WHERE dtp.DonationId = :dId AND dtp.ItemCode = :itemCode AND dt.`Index` = :index");
			try {
				if ($progress === true) {
					$completed = 1;
				} else {
					$completed = 0;
				}
				$parsedDId = intval($id);
				$statement->bindParam(':completed', $completed);
				$statement->bindParam(':dId', $parsedDId);
				preg_match("/^(\S*):([-\d]+)$/", $task, $parsed);
				$statement->bindParam(':itemCode', $parsed[1]);
				$parsedIndex = intval($parsed[2]);
				$statement->bindParam(':index', $parsedIndex);
				$success = $statement->execute();
			} finally {
				$statement->closeCursor();
			}
		}
	}
}