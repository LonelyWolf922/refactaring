<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="kadai2.css">
    <title>issue_management_system</title>
</head>
<body>
    <h1>イシュー管理システム</h1>
    <form action="bf_re.php" method="POST">
        <div id = "user_repo_name">
            <p>
                ユーザ名を入力 :
                <input type="text" name="user_name"><br>
                レポジトリ名を入力 :
                <input type="text" name="repo_name">
            </p>
        </div>
 
        <div id = "other">
            <p>
                イシューのタイトルを入力 :
                <input type="text" name="issue_title"><br>
                ラベルを選択 :
                <label><input type="radio" name="label" value="bug">バグ</label>
                <label><input type="radio" name="label" value="feature">機能要求</label><br>
                優先順位を設定 :
                <input type="text" name="priority"><br>
                イシューコミットIDを入力 :
                <input type="text" name="id">
            </p>
        </div>

        <div id = "button">
            <input type="submit" name = "send" value="送信">
            <input type="reset" value="クリア">
        </div>
    </form>

    <?php

    //データベースへの接続
    try {
        $pdo = new PDO("mysql:host=localhost;
                        dbname=issues_db;
                        user=root;
                        password=yuya0922;");
        echo "<div id = 'success'>"."データベースへの接続成功"."</div>";

    } catch (PDOException $e) {
        echo "<div id = 'conect_failure'>Error: " . $e->getMessage(). "</div>";
    }

    //送信ボタン押されたときの動作
    if (isset($_POST['send'])) {
        $user_name = $_POST["user_name"];
        $repo_name = $_POST["repo_name"];
        $issue_title = $_POST["issue_title"];
        if(isset($_POST['label']))
            $label = $_POST['label'];
        else
            $label = null;
        $priority = $_POST["priority"];
        $id = $_POST["id"];

        //入力条件の追加
        if(Empty($user_name) || Empty($repo_name) || Empty($issue_title) || Empty($priority) || Empty($id) || Empty($label)){
            if(Empty($user_name) || Empty($repo_name)){
                echo "<div id = 'warning'>ユーザ名またはレポジトリ名を入力してください。"."</div>";
            }
            if(Empty($issue_title) || Empty($priority) || Empty($id)){
                echo "<div id = 'warning'>イシューのタイトル、優先順位、イシューコミットIDを入力してください。"."</div>";
            }
            if(Empty($label)){
                echo "<div id = 'warning'>ラベルを選択してください。"."</div>";
            }
            if(Empty($priority)){
                echo "<div id = 'warning'>優先順位を入力してください。"."</div>";
            }
            else if(!is_numeric($priority)){
                echo "<div id = 'warning'>優先順位で使用できるのは1以上の整数のみです。"."</div>";
            }
            else if(is_numeric($priority) && $priority <= 0){
                echo "<div id = 'warning'>優先順位で使用できるのは1以上の整数のみです。"."</div>";
            }
        }
        else if(!is_numeric($priority)){
            echo "<div id = 'warning'>優先順位で使用できるのは1以上の整数のみです。"."</div>";
        }
        else if(is_numeric($priority) && $priority <= 0){
            echo "<div id = 'warning'>優先順位で使用できるのは1以上の整数のみです。"."</div>";
        }
        else{
            try{
                //イシューのタイトル、ラベル、優先順位、イシューコミットIDを表issuesに格納
                $sql_issues = 'INSERT INTO issues (username, reponame, title, label, priority, issue_commit) VALUES (?, ?, ?, ?, ?, ?)';
                $stmt_issues = $pdo->prepare($sql_issues);
                $stmt_issues->execute([$user_name, $repo_name, $issue_title, $label, $priority, $id]);
    
                echo "Record inserted successfully."."<br>";
            }
            catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            //ページのリロード
            header("Location:bf_re.php");      //相対パスに変更
        }
    }
        
    //更新ボタンが押されたときのテスト
    if (isset($_POST['modifide'])) {
        $row_id = $_POST['row_id'];
        $status_conf = $_POST['status_conf'];
        $issue_complete_ID = $_POST['issue_complete'];

        if(Empty($issue_complete_ID) && strcmp($status_conf, "completed") == 0){
            echo "<div id = 'warning'>Issue Complete Commit IDが入力されていません。</div>";
        }
        else{
            try {
                //取り組み状況とイシュー完了コミットIDを表に格納
                $sql_update = 'UPDATE issues SET show_status = ?, complete_commit = ? WHERE issue_id = ?';
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$status_conf, $issue_complete_ID, $row_id]);
                
                //表issuesの行の情報を取得
                $sql_url_select = 'SELECT username, reponame, issue_commit, complete_commit FROM issues';
                $stmt_url_select = $pdo->prepare($sql_url_select);
                $row_url = $stmt_url_select->execute();
    
                //表に格納されている情報からURLを生成
                while($row_url = $stmt_url_select->fetch(PDO::FETCH_ASSOC)){
                    $commit_url = "https://github.com/" . $row_url['username'] . "/" . $row_url['reponame'] . "/commit/" . $row_url['issue_commit'];
                    $worktree_url = "https://github.com/" . $row_url['username'] . "/" . $row_url['reponame'] . "/tree/" . $row_url['issue_commit'];
                    $commitdiff_url = "https://github.com/" . $row_url['username'] . "/" . $row_url['reponame'] . "/compare/" . $row_url['issue_commit'] . "..." . $row_url['complete_commit'];
    
                    //生成したURLを表に格納
                    $sql_url_update = "UPDATE issues SET commiturl = ?, worktreeurl = ?, commitdiffurl = ? WHERE issue_id = ?";
                    $stmt_url_update = $pdo->prepare($sql_url_update);
                    $stmt_url_update->execute([$commit_url, $worktree_url, $commitdiff_url, $row_id]);   
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            //ページのリロード
            header("Location:bf_re.php");      //相対パスに変更
        }
    }

    //表issuesの表示
    $sql_show = 'SELECT * FROM issues ORDER BY priority DESC';
    $stmt_show = $pdo->prepare($sql_show);
    $stmt_show->execute();
    echo "<table border='1'>
            <tr>
                <th>ユーザ名</th>
                <th>レポジトリ名</th>
                <th>タイトル</th>
                <th>ラベル</th>
                <th>優先順位</th>
                <th>進捗状況</th>
                <th>Issue Commit ID</th>
                <th>進捗状況の設定</th>
                <th>Issue Complete Commit ID</th>       
                <th>更新ボタン</th>
                <th>コミットのURL</th>
                <th>ワークツリーのURL</th>
                <th>コミットの差分URL</th>
            </tr>";
    
    //行の終わりまで表の内容を表示
    while ($row = $stmt_show->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<form action='bf_re.php' method='POST'>
                <td>" . htmlspecialchars($row['username']) . "</td>
                <td>" . htmlspecialchars($row['reponame']) . "</td>
                <td>" . htmlspecialchars($row['title']) . "</td>
                <td>" . htmlspecialchars($row['label']) . "</td>
                <td>" . htmlspecialchars($row['priority']) . "</td>
                <td>" . htmlspecialchars($row['show_status']) . "</td>
                <td>" . htmlspecialchars($row['issue_commit']) . "</td>
                <td>
                    <select name='status_conf'>
                        <option value='not_started'>未着手</option>
                        <option value='in_progress'>着手中</option>
                        <option value='completed'>完了</option>
                    </select>
                </td>
                <td><input type='text' name='issue_complete'></td>
                <td>
                    <input type='hidden' name='row_id' value=",$row['issue_id'],">
                    <input type='submit' name='modifide' value='更新'>
                </td>
                <td>" . "<a href = ", $row['commiturl'], "><p>コミットURL"."</p></a>" . "</td>
                <td>" . "<a href = ", $row['worktreeurl'], "><p>ワークツリーURL"."</p></a>" . "</td>
                <td>" . "<a href = ", $row['commitdiffurl'], "><p>コミット差分のURL"."</p></a>" . "</td>
                </form>
            </tr>";
    }
    echo "</table>";
    ?>
</body>
</html>