<html>
<head>
    <title>How2Sign Transcript DB</title>
</head>
<style>
    table {
        border-collapse: collapse;
        width: 100%;
    }

    th {
        text-align: center;
        padding: 8px;
    }

    td {
        text-align: left;
        padding: 8px;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    th {
        background-color: #4CAF50;
        color: white;
    }

    input[type="text"] {
        width: 70%;
        padding: 12px 20px;
        margin: 8px 0;
        box-sizing: border-box;
    }

    input[type="submit"] {
        background-color: #4CAF50;
        color: white;
        padding: 12px 20px;
        margin: 8px 0;
        border: none;
        cursor: pointer;
    }

    input[type="submit"]:hover {
        background-color: #45a049;
    }
</style>
<body>
<?php
require_once 'vendor/autoload.php';
$keyword = $_POST['keyword'] ?? "";
$chooseTable = $_POST['chooseDB'] ?? array();
$matchType = $_POST['matchType'] ?? "partial";
?>
<form action="/" method="post">
    <fieldset>
        <legend>Search Transcript</legend>
        <label for="keyword">Keyword:</label>
        <input type="text" id="keyword" name="keyword" placeholder="Enter keyword..."
               value="<?php echo htmlspecialchars($keyword); ?>">
        <br>
        <input type="radio" id="partialMatch" name="matchType"
               value="partial" <?php if ($matchType == "partial") echo "checked"; ?> checked>
        <label for="partialMatch">Partial Match</label>
        <input type="radio" id="exactMatch" name="matchType"
               value="exact" <?php if ($matchType == "exact") echo "checked"; ?>>
        <label for="exactMatch">Exact Match</label>
    </fieldset>
    <br>
    <fieldset>
        <legend>Choose Table</legend>
        <input type="checkbox" id="A" name="chooseDB[]"
               value="test" <?php if (in_array("test", $chooseTable)) echo "checked"; ?>>
        <label for="A">TEST</label>
        <input type="checkbox" id="B" name="chooseDB[]"
               value="validate" <?php if (in_array("validate", $chooseTable)) echo "checked"; ?>>
        <label for="B">VALID</label>
        <input type="checkbox" id="C" name="chooseDB[]"
               value="train" <?php if (in_array("train", $chooseTable)) echo "checked"; ?>>
        <label for="C">TRAIN</label>
        <br>
        <input type="checkbox" id="D" name="chooseDB[]"
               value="realigned_test" <?php if (in_array("realigned_test", $chooseTable)) echo "checked"; ?>>
        <label for="D">TEST(REALIGNED)</label>
        <input type="checkbox" id="E" name="chooseDB[]"
               value="realigned_validate" <?php if (in_array("realigned_validate", $chooseTable)) echo "checked"; ?>>
        <label for="E">VALID(REALIGNED)</label>
        <input type="checkbox" id="F" name="chooseDB[]"
               value="realigned_train" <?php if (in_array("realigned_train", $chooseTable)) echo "checked"; ?>>
        <label for="F">TRAIN(REALIGNED)</label>
    </fieldset>
    <br>
    <input type="submit" value="Submit">
</form>
<?php
if ($keyword == "" and $chooseTable == []) {
    exit();
}


$conn = [
    'driver' => 'pdo_pgsql',
    'host' => 'pgsql',
    'port' => 5432,
    'dbname' => 'public',
    'user' => 'root',
    'password' => 'password'
];
$config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
    [__DIR__ . '/src'],
    true,
    null,
    null,
    false
);
$entityManager = \Doctrine\ORM\EntityManager::create($conn, $config);

if ($matchType == "partial") {
    $keywords = explode(' ', $keyword);
    if ($keywords[0] == "") {
        unset($keywords[0]);
    }
} else {
    $keywords = array($keyword);
}

$result = [];
$rsm = new \Doctrine\ORM\Query\ResultSetMapping();
$rsm->addScalarResult("VIDEO_ID", "video_id");
$rsm->addScalarResult("SENTENCE_ID", "sentense_id");
$rsm->addScalarResult("SENTENCE_NAME", "sentence_name");
$rsm->addScalarResult("START_REALIGNED", "start_realigned");
$rsm->addScalarResult("END_REALIGNED", "end_realigned");
$rsm->addScalarResult("START", "start");
$rsm->addScalarResult("END", "end");
$rsm->addScalarResult("SENTENCE", "sentence");

foreach ($chooseTable as $tableName) {
    $parameters = array();
    $sql = "SELECT * FROM \"" . strtoupper($tableName) . "\"";
    if (count($keywords) != 0) {
        $sql .= " WHERE ";
        $conditions = [];
        foreach ($keywords as $index => $word) {
            $conditions[] = "\"SENTENCE\" ILIKE ?";
            $parameters[] = '%' . $word . '%';
        }
        $sql .= implode(' OR ', $conditions);
    }

    $query = $entityManager->createNativeQuery($sql, $rsm);
    foreach ($parameters as $index => $value) {
        $query->setParameter($index + 1, $value);
    }
    unset($value);
    unset($parameters);
    unset($conditions);
    $result[] = array("table" => $tableName, "data" => $query->getResult());
}
?>
<table>
    <thead>
    <tr>
        <th>Dataset Name</th>
        <th>Video ID</th>
        <th>Sentence ID</th>
        <th>Sentence Name</th>
        <th>Start</th>
        <th>End</th>
        <th>Sentence</th>
    </tr>
    </thead>
    <tbody>
    <?php if (isset($result)):
        $colors = array(
            "test" => "#ADD8E6",
            "validate" => "#90EE90",
            "train" => "#FFB6C1",
            "realigned_test" => "#20B2AA",
            "realigned_validate" => "#FFA07A",
            "realigned_train" => "#87CEFA"
        );
        foreach ($result as $row): ?>
            <?php if (isset($row['data']) && is_array($row['data'])): ?>
                <?php foreach ($row['data'] as $item): ?>
                    <tr>
                        <td style="background-color: <?php echo $colors[$row["table"]] ?>; text-align: center;"><?php echo $row["table"] ?></td>
                        <td><?php echo isset($item['video_id']) ? htmlspecialchars($item['video_id']) : ''; ?></td>
                        <td><?php echo isset($item['sentense_id']) ? htmlspecialchars($item['sentense_id']) : ''; ?></td>
                        <td><?php echo isset($item['sentence_name']) ? htmlspecialchars($item['sentence_name']) : ''; ?></td>
                        <td>
                            <?php
                            if (isset($item['start_realigned'])) {
                                echo htmlspecialchars($item['start_realigned']);
                            } else {
                                echo isset($item['start']) ? htmlspecialchars($item['start']) : '';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (isset($item['end_realigned'])) {
                                echo htmlspecialchars($item['end_realigned']);
                            } else {
                                echo isset($item['end']) ? htmlspecialchars($item['end']) : '';
                            }
                            ?>
                        </td>
                        <td><?php
                            $sentence = htmlspecialchars($item['sentence']);
                            foreach ($keywords as $keyword) {
                                $sentence = preg_replace("/\b" . preg_quote($keyword) . "\b/i",
                                    "<span style='background-color:yellow;'>$0</span>", $sentence);
                            }
                            echo $sentence; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>
