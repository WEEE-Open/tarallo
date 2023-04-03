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

			$types = array_unique(array_values($itemsTypes));

			foreach($types as $type) {
				$tasks_list = $tasks[$type] ?? null;
				if (is_array($tasks_list) && count($tasks_list) > 0) {
					$i = 0;
					foreach($tasks_list as $task) {
						$statement = $this->getPDO()->prepare("INSERT INTO DonationTasks(DonationId, `Index`, Title, ItemType) VALUES($donationId, $i, :task, :itemType)");
						$statement-> bindParam(':task', $task);
						$statement-> bindParam(':itemType', $type);
						$success = $statement->execute();
						$i++;
					}
				} else {
					$statement = $this->getPDO()->prepare("INSERT INTO DonationTasks(DonationId, `Index`, Title, ItemType) VALUES($donationId, -1, 'Done', :itemType)");
					$statement-> bindParam(':itemType', $type);
					$success = $statement->execute();
				}
			}

			foreach($itemsList as $item) {
				$statement = $this->getPDO()->prepare("INSERT INTO DonationItem(Donation,Code) VALUES(:donationId,:code)");
				$statement->bindParam(':donationId',$donationId);
				$statement->bindParam(':code',$item);

				$success = $statement->execute();
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

	public function updateDonation($id, $name, $location, $notes, $date, $itemsList, $tasks)
	{
		$oldDonation = $this->getDonation($id);

		$statement = $this->getPDO()->prepare("UPDATE Donations SET Name=:name, Location=:location, Date=:date, Notes=:notes WHERE Id=:id");
		try {
			$statement->bindParam(':name',$name);
			$statement->bindParam(':location',$location);
			$statement->bindParam(':date', $parsedDate);
			$statement->bindParam(':notes',$notes);
			$statement->bindParam(':id',$id);

			$success = $statement->execute();

			$added_items = array_diff($itemsList, array_keys($oldDonation["itemsType"]));
			$removed_items = array_diff(array_keys($oldDonation["itemsType"]), $itemsList);

			foreach($removed_items as $item) {
				$statement = $this->getPDO()->prepare("DELETE FROM DonationItem WHERE Donation=:id AND Code=:code");
				$statement->bindParam(':id',$id);
				$statement->bindParam(':code',$item);

				$success = $statement->execute();
				$tasksList = $statement->fetchAll(\PDO::FETCH_ASSOC);
			}

			foreach($added_items as $item) {
				$statement = $this->getPDO()->prepare("INSERT INTO DonationItem(Donation,Code) VALUES(:donationId,:code)");
				$statement->bindParam(':donationId',$id);
				$statement->bindParam(':code',$item);

				$success = $statement->execute();
			}
			
			$added_items_types = $this->database->itemDAO()->getTypesForItemCodes($added_items);

			// add tasks for types that were not in the donation before
			foreach(array_unique(array_values($added_items_types)) as $type) {
				if (!isset($donation["tasks"][$type])) {
					$tasks_list = $tasks[$type] ?? null;
					if (is_array($tasks_list) && count($tasks_list) > 0) {
						$i = 0;
						foreach($tasks_list as $task) {
							$statement = $this->getPDO()->prepare("INSERT INTO DonationTasks(DonationId, `Index`, Title, ItemType) VALUES($id, $i, :task, :itemType)");
							$statement-> bindParam(':task', $task);
							$statement-> bindParam(':itemType', $type);
							$success = $statement->execute();
							$i++;
						}
					} else {
						$statement = $this->getPDO()->prepare("INSERT INTO DonationTasks(DonationId, `Index`, Title, ItemType) VALUES($id, -1, 'Done', :itemType)");
						$statement-> bindParam(':itemType', $type);
						$success = $statement->execute();
					}
				}
			}
			
			// modify or add tasks for the types that already existed
			foreach($oldDonation["tasks"] as $type => $oldTasks) {
				$newTasks = $tasks[$type] ?? null;
				if (is_array($oldTasks)) {
					if (is_array($newTasks) && count($newTasks) > 0) {
						$i = 0;
						$newTasksWithoutRename = array_map(function ($t) {
							if (is_array($t)) return $t[0];
							return $t;
						}, $newTasks);
						$deletedTasks = array_filter($oldTasks, function ($t) use ($newTasksWithoutRename) {
							return array_search($t, $newTasksWithoutRename) === false;
						});
						foreach ($deletedTasks as $task) {
							$oldTaskIndex = array_search($deletedTasks[0], $oldTasks);
							$statement = $this->getPDO()->prepare("DELETE FROM DonationTasks WHERE DonationId=:donationId AND ItemType=:type AND `Index`=:oldIndex");
							$statement->bindParam(':donationId',$id);
							$statement->bindParam(':type',$type);
							$statement->bindParam(':oldIndex',$oldTaskIndex);
							$success = $statement->execute();
						}
						foreach ($newTasks as $task) {
							if (is_array($task)) { // this means it was renamed, value 0 is the old name, value 1 is the new name
								$oldTaskIndex = array_search($task[0], $oldTasks);
								if ($oldTaskIndex === false) { // means that it's new
									$statement = $this->getPDO()->prepare("INSERT INTO DonationTasks(DonationId, `Index`, Title, ItemType) VALUES($id, $i, :task, :itemType)");
									$statement-> bindParam(':task', $task[1]);
									$statement-> bindParam(':itemType', $type);
									$success = $statement->execute();
								} else {
									$statement = $this->getPDO()->prepare("UPDATE DonationTasks SET Title=:title, `Index`=:newIndex WHERE DonationId=:donationId AND ItemType=:type AND `Index`=:oldIndex");
									$statement->bindParam(':title',$task[1]);
									$statement->bindParam(':newIndex',$i);
									$statement->bindParam(':donationId',$id);
									$statement->bindParam(':type',$type);
									$statement->bindParam(':oldIndex',$oldTaskIndex);
									$success = $statement->execute();
								}
							} else {
								$oldTaskIndex = array_search($task, $oldTasks);
								if ($oldTaskIndex !== $i) {
									if ($oldTaskIndex === false) {
										$statement = $this->getPDO()->prepare("INSERT INTO DonationTasks(DonationId, `Index`, Title, ItemType) VALUES($id, $i, :task, :itemType)");
										$statement-> bindParam(':task', $task);
										$statement-> bindParam(':itemType', $type);
										$success = $statement->execute();
									} else {
										$statement = $this->getPDO()->prepare("UPDATE DonationTasks SET `Index`=:newIndex WHERE DonationId=:donationId AND ItemType=:type AND `Index`=:oldIndex");
										$statement->bindParam(':newIndex',$i);
										$statement->bindParam(':donationId',$id);
										$statement->bindParam(':type',$type);
										$statement->bindParam(':oldIndex',$oldTaskIndex);
										$success = $statement->execute();
									}
								}
							}
							$i++;
						}
					} else {
						$statement = $this->getPDO()->prepare("DELETE FROM DonationTasks WHERE DonationId=:donationId AND ItemType=:type");
						$statement->bindParam(':donationId',$id);
						$statement->bindParam(':type',$type);
						$success = $statement->execute();

						$statement = $this->getPDO()->prepare("INSERT INTO DonationTasks(DonationId, `Index`, Title, ItemType) VALUES($id, -1, 'Done', :itemType)");
						$statement-> bindParam(':itemType', $type);
						$success = $statement->execute();
					}
				} else {
					if (is_array($newTasks) && count($newTasks) > 0) {
						$statement = $this->getPDO()->prepare("DELETE FROM DonationTasks WHERE DonationId=:donationId AND ItemType=:type");
						$statement->bindParam(':donationId',$id);
						$statement->bindParam(':type',$type);
						$success = $statement->execute();

						$i = 0;
						foreach($newTasks as $task) {
							$statement = $this->getPDO()->prepare("INSERT INTO DonationTasks(DonationId, `Index`, Title, ItemType) VALUES($id, $i, :task, :itemType)");
							$statement-> bindParam(':task', $task);
							$statement-> bindParam(':itemType', $type);
							$success = $statement->execute();
							$i++;
						}
					}
				}
			}

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

	public function deleteDonation($id)
	{
		$statement = $this->getPDO()->prepare("DELETE FROM Donations WHERE Id=:id");
		try {
			$statement->bindParam(':id', $id);
			$success = $statement->execute();
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