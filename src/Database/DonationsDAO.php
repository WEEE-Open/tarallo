<?php 



namespace WEEEOpen\Tarallo\Database;

use XLSXWriter;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\ItemWithCode;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

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
				$type = $type ?? 'other';
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
			for($i = 0; $i < count($donations); $i++) {
				$donations[$i]["isCompleted"] = boolval($donations[$i]["isCompleted"]);
				if ($donations[$i]["date"] ?? null !== null) {
					$donations[$i]["date"] = date_format(date_create($donations[$i]["date"]),"Y/m/d");
				} else {
					unset($donations[$i]["date"]);
				}
				if ($donations[$i]["totalTasks"] !== 0)
					$donations[$i]["progress"] = round($donations[$i]["completedTasks"] / $donations[$i]["totalTasks"]*100, 0, PHP_ROUND_HALF_DOWN);
				else if($donations[$i]["isCompleted"]) $donations[$i]["progress"] = 100;
				else $donations[$i]["progress"] = 0;
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
				$type = $type ?? 'other';
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
				$type = $type ?? 'other';
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
				if ($donation["date"] ?? null !== null) {
					$donation["date"] = date_format(date_create($donation["date"]),"Y/m/d");
				} else {
					unset($donation["date"]);
				}

				$statement = $this->getPDO()->prepare("SELECT Code FROM DonationItem WHERE Donation = :id");
				$statement->bindParam(':id',$id);

				$success = $statement->execute();
				$itemsListAssoc = $statement->fetchAll(\PDO::FETCH_ASSOC);

				$itemsList = [];
				foreach($itemsListAssoc as $i) {
					array_push($itemsList, $i["Code"]);
				}

				$donation["itemsType"] = $this->database->itemDAO()->getTypesForItemCodes($itemsList);

				if (!$donation["isCompleted"]) {
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
				} else {
					$donation["progress"] = 100;
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
			return $statement->rowCount() > 0;
		} finally {
			$statement->closeCursor();
		}
	}

	public function completeDonation($id)
	{
		$statement = $this->getPDO()->prepare("UPDATE Donations SET IsCompleted=1 WHERE Id=:id");
		try {
			$statement->bindParam(':id', $id);
			$success = $statement->execute();
			return $statement->rowCount() > 0;
		} finally {
			$statement->closeCursor();
		}
	}

	public function uncompleteDonation($id)
	{
		$statement = $this->getPDO()->prepare("UPDATE Donations SET IsCompleted=0 WHERE Id=:id");
		try {
			$statement->bindParam(':id', $id);
			$success = $statement->execute();
			return $statement->rowCount() > 0;
		} finally {
			$statement->closeCursor();
		}
	}

	public function updateTasksProgress($id, $tasks)
	{
		$statement = $this->getPDO()->prepare("SELECT IsCompleted FROM Donations WHERE Id = :id");
		try {
			$parsedDId = intval($id);
			$statement->bindParam(':id', $parsedDId);
			$success = $statement->execute();
			$donation = $statement->fetch(\PDO::FETCH_ASSOC);
			if ($donation["IsCompleted"] === 1)
			throw new \LogicException(
				'Can\'t update a task in a completed donation'
			);
		} finally {
			$statement->closeCursor();
		}

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

	public function generateExcelSummary($id)
	{
		$donation = $this->getDonation($id);

		if ($donation === false) return false;

		$itemsProperties = [];

		foreach($donation["itemsType"] as $itemId => $_) {
			try {
				$item = new ItemCode($itemId);
			} catch (ValidationException $e) {
				$itemsProperties[$itemId] = null;
			}
			$itemsProperties[$itemId] = $this->database->itemDAO()->getItem($item);
		}

		$writer = new XLSXWriter();
		$writer->setAuthor('Tarallo'); 
		foreach(array_unique(array_values($donation["itemsType"])) as $type) { // Good luck to anyone that will have to debug/modify this code
			$displayType = FeaturePrinter::FEATURES_ENUM['type'][$type] ?? 'Other';
			$itemsOfType = array_filter($donation["itemsType"], function ($it) use ($type) {return $it === $type;});
			$rootProperties = [];
			$groupedPropertiesForSubItems = [];
			$countOfType = [];
			$groupedPropertiesValuesForSubItems = [];
			foreach($itemsOfType as $item => $_) {
				$rootProperties = array_unique(array_merge($rootProperties, array_keys($itemsProperties[$item]->getFeatures())));
				$itemsToCheck = $itemsProperties[$item]->getContent();
				for($i = 0; $i < count($itemsToCheck); $i++) {
					$content = $itemsToCheck[$i];
					$type = $content->getFeatures()["type"]->value ?? "unknown";
					$groupedPropertiesForSubItems[$type] = array_unique(array_merge($groupedPropertiesForSubItems[$type] ?? [], array_keys($content->getFeatures())));
					$groupedPropertiesValuesForSubItems[$item] = $groupedPropertiesValuesForSubItems[$item] ?? [];
					$groupedPropertiesValuesForSubItems[$item][$type] = $groupedPropertiesValuesForSubItems[$item][$type] ?? [];
					array_push($groupedPropertiesValuesForSubItems[$item][$type], $content);
					$countOfType[$type] = max(($countOfType[$type] ?? 0), count($groupedPropertiesValuesForSubItems[$item][$type]));
					if (count($content->getContent()) > 0) {
						array_push($itemsToCheck, ...$content->getContent());
					}
				}
			}
			$rootProperties = array_filter($rootProperties, function ($t) {
				return !in_array($t, ["type", "owner", "note", "working"]);
			});
			$groupedPropertiesForSubItems = array_map(function ($arr) {
				return array_filter($arr, function ($t) {
					return !in_array($t, ["type", "owner", "note", "working"]);
				});
			}, $groupedPropertiesForSubItems);
			if (count($countOfType)>0) {
				$writer->writeSheetRow($displayType, array_merge(array_fill(0, count($rootProperties)+1, ''), 
					...array_map(function ($type, $arr) use ($countOfType) {
						if (($countOfType[$type]??0) > 1) {
							$acc = [];
							for ($i = 0; $i < $countOfType[$type]; $i++)
								array_push($acc, FeaturePrinter::FEATURES_ENUM['type'][$type] . ' ' . $i, ...array_fill(0, count($arr), ''));
							return $acc;
						} else
							return [FeaturePrinter::FEATURES_ENUM['type'][$type], ...array_fill(0, count($arr), '')];
					},
					array_keys($groupedPropertiesForSubItems),
					array_values($groupedPropertiesForSubItems)
				)), ['valign' => 'center', 'halign' => 'center']);
				$offset = count($rootProperties);
				$writer->markMergedCell($displayType, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = $offset);
				$offset += 1;
				foreach($groupedPropertiesForSubItems as $type => $n) {
					for ($i = 0; $i < $countOfType[$type]; $i++) {
						$l = count($n);
						$writer->markMergedCell($displayType, $start_row = 0, $start_col = $offset, $end_row = 0, $end_col = $offset + $l);
						$offset += $l + 1;
					}
				}
				$writer->writeSheetRow($displayType, array_merge(["Id"], 
					array_map(function ($f) {return FeaturePrinter::FEATURES[$f] ?? $f;}, $rootProperties), 
					...array_map(function ($type, $arr) use ($countOfType) {
						if (($countOfType[$type]??0) > 1) {
							$acc = [];
							for ($i = 0; $i < $countOfType[$type]; $i++)
								array_push($acc, "Id", ...array_map(function ($f) {return FeaturePrinter::FEATURES[$f] ?? $f;}, $arr));
							return $acc;
						} else
							return ["Id", ...array_map(function ($f) {return FeaturePrinter::FEATURES[$f] ?? $f;}, $arr)];
					}, array_keys($groupedPropertiesForSubItems), array_values($groupedPropertiesForSubItems))
				));
				foreach($itemsOfType as $item => $_) {
					$writer->writeSheetRow($displayType, [$item,
						...array_map(function ($f) use ($item, $itemsProperties) {return $itemsProperties[$item]->getFeatureValue($f) ?? '';}, $rootProperties),
						...array_merge(...array_map(function ($type, $arr) use ($item, $countOfType, $groupedPropertiesValuesForSubItems) {
							$acc = [];
							for ($i = 0; $i < $countOfType[$type]; $i++) {
								if (!isset($groupedPropertiesValuesForSubItems[$item][$type]) || count($groupedPropertiesValuesForSubItems[$item][$type])<=$i) {
									array_push($acc, ...array_fill(0, count($arr), ''));
								} else {
									array_push($acc, $groupedPropertiesValuesForSubItems[$item][$type][$i]->getCode(), ...array_map(function ($f) use ($item, $type, $i, $groupedPropertiesValuesForSubItems) {return $groupedPropertiesValuesForSubItems[$item][$type][$i]->getFeatureValue($f) ?? '';}, $arr));
								}
							}
							return $acc;
						}, array_keys($groupedPropertiesForSubItems), array_values($groupedPropertiesForSubItems)))
					]);
				}
			} else {
				$writer->writeSheetRow($displayType, array_merge(["Id"], 
					array_map(function ($f) {return FeaturePrinter::FEATURES[$f] ?? $f;}, $rootProperties)
				));
				foreach($itemsOfType as $item => $_) {
					$writer->writeSheetRow($displayType, [$item, ...array_map(function ($f) use ($item, $itemsProperties) {return $itemsProperties[$item]->getFeatureValue($f) ?? '';}, $rootProperties)]);
				}
			}
		}

		return [
			$writer,
			"donation summary " . $donation["name"] . ".xlsx"
		];
	}

	public function getDonationsForItem($itemWithCode)
	{
		$output = [];
		$statement = $this->getPDO()->prepare("SELECT Donation, Name FROM Donations INNER JOIN DonationItem ON Donations.Id = DonationItem.Donation WHERE DonationItem.Code = :item AND IsCompleted = 0");
		try {
			$itemCode =  $itemWithCode->getCode();
			$statement->bindParam(':item', $itemCode);
			$success = $statement->execute();
			$donations = $statement->fetchAll(\PDO::FETCH_ASSOC);
			foreach($donations as $d) {
				$statement = $this->getPDO()->prepare("SELECT Title, `Index`, Completed FROM DonationTasks DT INNER JOIN DonationTasksProgress DTP ON DT.Id = DTP.TaskId WHERE DT.DonationId = :id AND DTP.ItemCode = :item ORDER BY `Index` ASC");
				$statement->bindParam(':id', $d["Donation"]);
				$statement->bindParam(':item', $itemCode);
				$success = $statement->execute();
				$tasks = $statement->fetchAll(\PDO::FETCH_ASSOC);
				$output["".$d["Donation"]] = ["id" => $d["Donation"], "name" => $d["Name"]];
				if (count($tasks) === 1 && $tasks[0]["Index"] === -1) {
					$output["".$d["Donation"]]["tasksName"] = "Done";
					$output["".$d["Donation"]]["tasksValue"] = boolval($tasks[0]["Completed"]);
				} else {
					$output["".$d["Donation"]]["tasksName"] = [];
					$output["".$d["Donation"]]["tasksValue"] = [];
					foreach($tasks as $t) {
						$output["".$d["Donation"]]["tasksName"][] = $t["Title"];
						$output["".$d["Donation"]]["tasksValue"][] = boolval($t["Completed"]);
					}
				}
			}
		} finally {
			$statement->closeCursor();
		}
		return $output;
	}

	public function addDonationsToItem($itemWithCode)
	{
		$donations = $this->getDonationsForItem($itemWithCode);
		foreach($donations as $id => $donation) {
			$itemWithCode->addDonation($id, $donation);
		}
	}
}
