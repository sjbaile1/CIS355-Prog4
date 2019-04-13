<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
class Customer {
    public $id;
    public $name;
    public $email;
    public $mobile;
    public $description;
    private $noerrors = true;
    private $nameError = null;
    private $descriptionError = null;
    private $emailError = null;
    private $mobileError = null;
    private $title = "Customer";
    private $tableName = "customers";
    private $urlName =  "customer";
    public $pictureContent;
    public $fileName;
    public $tempFileName;
    public $fileSize;
    public $fileType;
    function create_record() { // display "create" form
        $this->generate_html_top (1);
        $this->generate_form_picture($this->pictureContent, "content", "create", "required");
        $this->generate_form_group("input","description", $this->descriptionError, $this->description, "required");
        $this->generate_form_group("input","name", $this->nameError, $this->name, "autofocus");
        $this->generate_form_group("input","email", $this->emailError, $this->email);
        $this->generate_form_group("input","mobile", $this->mobileError, $this->mobile);
        $this->generate_html_bottom (1);
    } // end function create_record()
    function read_record($id) { // display "read" form
        $this->select_db_record($id);
        $this->generate_html_top(2);
        $this->generate_form_picture($this->pictureContent, "content", "read");
        $this->generate_form_group("input","description", $this->descriptionError, $this->description, "disabled");
        $this->generate_form_group("input","name", $this->nameError, $this->name, "disabled");
        $this->generate_form_group("input","email", $this->emailError, $this->email, "disabled");
        $this->generate_form_group("input","mobile", $this->mobileError, $this->mobile, "disabled");
        $this->generate_html_bottom(2);
    } // end function read_record()
    function update_record($id) { // display "update" form
        if($this->noerrors) $this->select_db_record($id);
        $this->generate_html_top(3, $id);
        $this->generate_form_picture($this->pictureContent, "content", "update");
        $this->generate_form_group("input","description", $this->descriptionError, $this->description, "required");
        $this->generate_form_group("input","name", $this->nameError, $this->name, "autofocus onfocus='this.select()'");
        $this->generate_form_group("input","email", $this->emailError, $this->email);
        $this->generate_form_group("input","mobile", $this->mobileError, $this->mobile);
        $this->generate_html_bottom(3);
    } // end function update_record()
    function delete_record($id) { // display "read" form
        $this->select_db_record($id);
        $this->generate_html_top(4, $id);
        $this->generate_form_picture($this->pictureContent, "content", "delete");
        $this->generate_form_group("input","description", $this->descriptionError, $this->description, "disabled");
        $this->generate_form_group("input","name", $this->nameError, $this->name, "disabled");
        $this->generate_form_group("input","email", $this->emailError, $this->email, "disabled");
        $this->generate_form_group("input","mobile", $this->mobileError, $this->mobile, "disabled");
        $this->generate_html_bottom(4);
    } // end function delete_record()
    /*
     * This method inserts one record into the table,
     * and redirects user to List, IF user input is valid,
     * OTHERWISE it redirects user back to Create form, with errors
     * - Input: user data from Create form
     * - Processing: INSERT (SQL)
     * - Output: None (This method does not generate HTML code,
     *   it only changes the content of the database)
     * - Precondition: Public variables set (name, email, mobile)
     *   and database connection variables are set in datase.php.
     *   Note that $id will NOT be set because the record
     *   will be a new record so the SQL database will "auto-number"
     * - Postcondition: New record is added to the database table,
     *   and user is redirected to the List screen (if no errors),
     *   or Create form (if errors)
     */
    function insert_db_record () {
// put the content of the file into a variable, $content
        $fp      = fopen($this->tempFileName, 'r');
        $content = fread($fp, filesize($this->tempFileName));
        fclose($fp);
        if ($this->fieldsAllValid ()) { // validate user input
            //echo "name " . $this->name . "Email " . $this->email, "Mobile " . $this->mobile, "File Name " . $this->fileName, "File Type " .  $this->fileType, " content " .  $content, "file size " .  $this->fileSize;
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "INSERT INTO $this->tableName (name,email,mobile,filename,filetype,content,filesize,description) values(?, ?, ?, ?, ?, ?, ?, ?)";
            $q = $pdo->prepare($sql);
            $q->execute(array($this->name,$this->email,$this->mobile, $this->fileName, $this->fileType, $content, $this->fileSize, $this->description));
            $this->id = $pdo->lastInsertId();
            $absolutePath = $this->store_file_locally();
            $sql = "UPDATE $this->tableName  set absolutepath = ? WHERE id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($absolutePath, $this->id));
            Database::disconnect();
            header("Location: $this->urlName.php"); // go back to "list"
        }
        else {
            // if not valid data, go back to "create" form, with errors
            // Note: error fields are set in fieldsAllValid ()method
            $this->create_record();
        }
    } // end function insert_db_record
    private function select_db_record($id) {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM $this->tableName where id = ?";
        $q = $pdo->prepare($sql);
        $q->execute(array($id));
        $data = $q->fetch(PDO::FETCH_ASSOC);
        Database::disconnect();
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->mobile = $data['mobile'];
        $this->pictureContent = $data['content'];
        $this->description = $data['description'];
    } // function select_db_record()
    function update_db_record ($id) {
        if ($this->tempFileName != null) {
// put the content of the file into a variable, $content
            $fp = fopen($this->tempFileName, 'r');
            $content = fread($fp, filesize($this->tempFileName));
            fclose($fp);
            $this->id = $id;
            if ($this->fieldsAllValid()) {
                $this->noerrors = true;
                $absolutePath = $this->store_file_locally();
                $pdo = Database::connect();
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $sql = "UPDATE $this->tableName  set name = ?, email = ?, mobile = ?, filename = ?, filetype = ?, content = ?, filesize = ?, absolutePath = ?, description = ?  WHERE id = ?";
                $q = $pdo->prepare($sql);
                $q->execute(array($this->name, $this->email, $this->mobile, $this->fileName, $this->fileType, $content, $this->fileSize, $absolutePath, $this->description, $this->id));
                Database::disconnect();
                header("Location: $this->urlName.php");
            } else {
                $this->noerrors = false;
                $this->update_record($id);  // go back to "update" form
            }
        } else {
            $this->id = $id;
            if ($this->fieldsAllValid()) {
                $this->noerrors = true;
                $pdo = Database::connect();
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $sql = "UPDATE $this->tableName  set name = ?, email = ?, mobile = ?, description = ?  WHERE id = ?";
                $q = $pdo->prepare($sql);
                $q->execute(array($this->name, $this->email, $this->mobile, $this->description, $this->id));
                Database::disconnect();
                header("Location: $this->urlName.php");
            } else {
                $this->noerrors = false;
                $this->update_record($id);  // go back to "update" form
            }
        }
    } // end function update_db_record
    function delete_db_record($id) {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "DELETE FROM $this->tableName WHERE id = ?";
        $q = $pdo->prepare($sql);
        $q->execute(array($id));
        Database::disconnect();
        header("Location: $this->urlName.php");
    } // end function delete_db_record()
    
    function store_file_locally(){
        // creates sub-directory to store uploaded files
        $fileLocation = "uploads1/" . $this->id ."/";
        $fileFullPath = $fileLocation . $this->fileName;
        if (!file_exists($fileLocation))
            mkdir ($fileLocation, 0777, true); // create subdirectory, if necessary
        else
            array_map('unlink', glob($fileLocation . "*"));
        move_uploaded_file($this->tempFileName, $fileFullPath);
        chmod($fileFullPath, 0777);
	return realpath($fileFullPath);
    }
    private function generate_html_top ($fun, $id=null) {
        switch ($fun) {
            case 1: // create
                $funWord = "Create"; $funNext = "insert_db_record";
                break;
            case 2: // read
                $funWord = "Read"; $funNext = "none";
                break;
            case 3: // update
                $funWord = "Update"; $funNext = "update_db_record&id=" . $id;
                break;
            case 4: // delete
                $funWord = "Delete"; $funNext = "delete_db_record&id=" . $id;
                break;
            default:
                echo "Error: Invalid function: generate_html_top()";
                exit();
                break;
        }
        echo "<!DOCTYPE html>
        <html>
            <head>
                <title>$funWord a $this->title</title>
                    ";
        echo "
                <meta charset='UTF-8'>
                <script src=\"https://code.jquery.com/jquery-3.3.1.min.js\"
                integrity=\"sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=\"
                crossorigin=\"anonymous\"></script>
                <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                <style>label {width: 5em;}</style>
                    ";
        echo "
            </head>";
        echo "
            <body>
                <div class='container'>
                    <div class='span10 offset1'>
                        <p class='row'>
                            <h3>$funWord a $this->title</h3>
                        </p>
                        <form class='form-horizontal' action='$this->urlName.php?fun=$funNext' method='post' enctype='multipart/form-data' onsubmit='return Validate(this);'>                        
                    ";
    } // end function generate_html_top()
    private function generate_html_bottom ($fun) {
        switch ($fun) {
            case 1: // create
                $funButton = "<button type='submit' class='btn btn-success'>Create</button>";
                break;
            case 2: // read
                $funButton = "";
                break;
            case 3: // update
                $funButton = "<button type='submit' class='btn btn-warning'>Update</button>";
                break;
            case 4: // delete
                $funButton = "<button type='submit' class='btn btn-danger'>Delete</button>";
                break;
            default:
                echo "Error: Invalid function: generate_html_bottom()";
                exit();
                break;
        }
        echo " 
                            <div class='form-actions'>
                                $funButton
                                <a class='btn btn-secondary' href='$this->urlName.php'>Back</a>
                            </div>
                        </form>
                    </div>
                </div> <!-- /container -->
            </body>
        </html>
        <script>
        // Code taken from https://canvas.svsu.edu/courses/28460/files/folder/_file_upload
            var _validFileExtensions = [\".jpg\", \".jpeg\", \".gif\", \".png\"];    
            function Validate(oForm) {
                var arrInputs = oForm.getElementsByTagName(\"input\");
                for (var i = 0; i < arrInputs.length; i++) {
                    var oInput = arrInputs[i];
                    if (oInput.type == \"file\") {
                        var sFileName = oInput.value;
                        if (sFileName.length > 0) {
                            var blnValid = false;
                            for (var j = 0; j < _validFileExtensions.length; j++) {
                                var sCurExtension = _validFileExtensions[j];
                                if (sFileName.substr(sFileName.length - sCurExtension.length, sCurExtension.length).toLowerCase() == sCurExtension.toLowerCase()) {
                                    blnValid = true;
                                    break;
                                }
                            }
                            
                            if (!blnValid) {
                                alert(\"Sorry, \" + sFileName + \" is invalid, allowed extensions are: \" + _validFileExtensions.join(\", \"));
                                return false;
                            }
                                                        
                        }
                    }
                }
                return true;
            }
        </script>
                    ";
    } // end function generate_html_bottom()
    private function generate_form_group ($node, $label, $labelError, $val, $modifier="") {
        echo "<div class='form-group'";
        echo !empty($labelError) ? ' alert alert-danger ' : '';
        echo "'>";
        echo "<label class='control-label'>$label &nbsp;</label>";
        //echo "<div class='controls'>";
        echo "<" . $node . " "
            . "name='$label' "
            . "type='text' "
            . "$modifier "
            . "placeholder='$label' "
            . "value='";
        echo !empty($val) ? $val : '';
        echo "'>";
        if (!empty($labelError)) {
            echo "<span class='help-inline'>";
            echo "&nbsp;&nbsp;" . $labelError;
            echo "</span>";
        }
        //echo "</div>"; // end div: class='controls'
        echo "</div>"; // end div: class='form-group'
    } // end function generate_form_group()
    private function generate_form_picture($content, $type, $action, $required="")
    {
        switch ($type){
            case "content"://in the case that it 
                echo '<img id=imgDisplay overflow=hidden width=200 height=200 src="data:image/jpeg;base64,' . base64_encode( $content ).'"/>';
                break;
            case "path":
                //echo '<img id=imgDisplay overflow=hidden width=200 height=20 src="data:image/jpeg;base64,' . base64_encode( $content ).'"/>';
                break;
        }
        switch ($action) {
            case "create":
            case "update":
                // Original code here: https://stackoverflow.com/questions/16207575/how-to-preview-a-image-before-and-after-upload
                echo '<br><input type="file" name="Filename"' . $required . ' onchange="readURL(this);">
                        <script type="text/javascript">
                        function readURL(input) {
                            if (input.files[0].size > 2000000) {
                                input.value = null;
                                alert("The picture cannot be larger than 2MB in size!");
                            }
                            
                            if (input.files && input.files[0]) {
                                var reader = new FileReader();
                                reader.onload = function (e) {
                                    $(\'#imgDisplay\').attr(\'src\', e.target.result);
                                }
                
                                reader.readAsDataURL(input.files[0]);
                            } else {
                                    $(\'#imgDisplay\').attr(\'src\', null);
                            }
                        }
                        </script>';
                break;
        }
    }
    private function fieldsAllValid () {
        $valid = true;
        if (empty($this->name)) {
            $this->nameError = 'Please enter Name';
            $valid = false;
        }
        if (empty($this->email)) {
            $this->emailError = 'Please enter Email Address';
            $valid = false;
        }
        else if ( !filter_var($this->email,FILTER_VALIDATE_EMAIL) ) {
            $this->emailError = 'Please enter a valid email address: me@mydomain.com';
            $valid = false;
        }
        if (empty($this->mobile)) {
            $this->mobileError = 'Please enter Mobile phone number';
            $valid = false;
        }
        return $valid;
    } // end function fieldsAllValid()
    function list_records() {
        echo "<!DOCTYPE html>
        <html>
            <head>
                <title>$this->title" . "s" . "</title>
                    ";
        echo "
                <meta charset='UTF-8'>
                <script src=\"https://code.jquery.com/jquery-3.3.1.min.js\"
                integrity=\"sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=\"
                crossorigin=\"anonymous\"></script>
                <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                    ";
        echo "
            </head>
            <body>
                <a href='https://github.com/sjbaile1/CIS355-Prog4'>GitHub</a><br />
               
                <div class='container'>
                    <p class='row'>
                        <h3>$this->title" . "s" . "</h3>
                    </p>
                    <p>
                        <a href='$this->urlName.php?fun=display_create_form' class='btn btn-success'>Create</a>
                        <a href='logout.php' class='btn btn-danger'>Logout</a>
                    </p>
                    <div class='row'>
                        <table class='table table-striped table-bordered'>
                            <thead>
                                <tr>
									<th>Name</th>
									<th>Picture</th>
									<th>Description</th>
									<th>Subdirectory Picture</th>
                                    <th>Sever Location</th> 	
                                    <th>Email</th>
                                    <th>Mobile</th>	 
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                    ";
        $pdo = Database::connect();
        $sql = "SELECT * FROM $this->tableName ORDER BY id DESC";
        foreach ($pdo->query($sql) as $row) {
            echo "<tr>";
			echo "<td>". $row["name"] . "</td>";
			echo "<td>" . '<img width=50 height=50 src="data:image/jpeg;base64,' . base64_encode( $row['content'] ).'"/>' . "</td>";
		    echo "<td>". $row["description"] . "</td>";
			echo "<td>" . '<img width=50 height=50 src="uploads1/' . $row["id"] . "/" . $row['filename'] .'"/>' . "</td>";
            echo "<td><a href='" . $row["absolutepath"] . "' target='_blank'>". $row["absolutepath"] . "</a></td>";
            echo "<td>". $row["email"] . "</td>";
            echo "<td>". $row["mobile"] . "</td>";
            echo "<td width=250>";
            echo "<a class='btn btn-info' href='$this->urlName.php?fun=display_read_form&id=".$row["id"]."'>Read</a>";
            echo "&nbsp;";
            echo "<a class='btn btn-warning' href='$this->urlName.php?fun=display_update_form&id=".$row["id"]."'>Update</a>";
            echo "&nbsp;";
            echo "<a class='btn btn-danger' href='$this->urlName.php?fun=display_delete_form&id=".$row["id"]."'>Delete</a>";
            echo "</td>";
            echo "</tr>";
        }
        Database::disconnect();
        echo "
                            </tbody>
                        </table>
                    </div>
                </div>
            </body>
        </html>
                    ";
    } // end function list_records()
} // end class Customer