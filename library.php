<?php
header('Content-Type: text/html; charset=UTF-8');
error_reporting(0);

require __DIR__.'/vendor/autoload.php';

use Spipu\Html2Pdf\Html2Pdf;

echo "<style>

.logo {
    width: 50vh;
    float: left;
    position: absolute;
    right:0px;
    top:0px;
}

.hr {
    width:30vh;
    color: grey;
}

.caption {
    margin-top: 20vh; 
    text-align: center;
}

.table {
    border: 2px solid; 
    box-shadow: 5px 10px #888888;
}

.table form {
    margin:0px;
    height:3vh;
    vertical-align: middle;
}

.table button {
    width:100%;
    margin:0px;
    height:3vh;
    vertical-align: middle;
}

.tableHeader {
    margin-top: 1vh; 
}

.nav button{
    position: absolute; /* Position them relative to the browser window */
    padding: 15px; /* 15px padding */
    width: 210px; /* Set a specific width */
    height: 80px;
    text-decoration: none; /* Remove underline */
    font-size: 20px; /* Increase font size */
    color: white; /* White text color */
    border-radius: 0 5px 5px 0; /* Rounded corners on the top right and bottom right side */
}

.nav h1{
    position: absolute; /* Position them relative to the browser window */
    left: 90px;
    margin-top:-8px;
    margin-bottom:auto;
    color: black;
    font-size:16px;
}

.nav button img {
    position: absolute;
    heigth:80px;
    weigth:80px;
    top:-4px;
    left:0px;
}

.addBook {
    top: 30vh;
    right: -125px; /* Position them outside of the screen */
    transition: 0.3s; /* Add transition on hover */
    background-color: #4CAF50;
}

.addBook:hover {
    right: -10px;
}

.searchBook {
    top: 40vh;
    right: -125px; /* Position them outside of the screen */
    transition: 0.3s; /* Add transition on hover */
    background-color: #2196F3;
}

.searchBook:hover {
    right: -10px;
}

.borrowList {
    top: 50vh;
    right: -125px; /* Position them outside of the screen */
    transition: 0.3s; /* Add transition on hover */
    background-color: #f44336;
}

.borrowList:hover {
    right: -10px;
}

</style>";

class filterItem {
    public $enabled;
    public string $operator;
    public string $value;

    public function __construct(bool $enabled,string $operator,string $value){
        $this->enabled = $enabled;
        $this->operator = $operator;
        $this->value = $value;
    }
}

class filterOptions {
    /**
     * Author of the Book.
     * enabled = true || false
     * value = ""
     */
    private $authorOptions;

    /**
     * ISBN Number of the Book.
     * enabled = true || false
     * operator = "OR" || "AND"
     * value = ""
     */
    private $isbnOptions;

    /**
     * Year of the Book.
     * enabled = true || false
     * operator = ">=" || "<="
     * value = ""
     */
    private $yearOptions;

    /**
     * Building where the Book is stored.
     * enabled = true || false
     * operator = "OR" || "AND"
     * value = "K" || "F"
     */
    private $buildingOptions;

    /**
     * Subject of the Book.
     * enabled = true || false
     * operator = "OR" || "AND"
     * value = ""
     */
    private $subjectOptions;

    public function __construct($authorOptions, $isbnOptions, $yearOptions, $buildingOptions, $subjectOptions){
        $this->authorOptions = $authorOptions;
        $this->isbnOptions = $isbnOptions;
        $this->yearOptions = $yearOptions;
        $this->buildingOptions = $buildingOptions;
        $this->subjectOptions = $subjectOptions;
    }

    public function buildSQLString(){
        $sql = "";
        if($this->authorOptions->enabled) {
            $sql = $sql ."bookAuthor='".$this->authorOptions->value."' ";
        }

        if($this->isbnOptions->enabled) {
            if($this->isbnOptions->operator != "") {
                $sql = $sql .$this->isbnOptions->operator." ";
            }
            $sql = $sql ."bookIsbn='".$this->isbnOptions->value."' ";
        }
        
        if($this->yearOptions->enabled) {
            if($this->yearOptions->operator != "") {
                $sql = $sql .$this->yearOptions->operator." ";
            }
            $sql = $sql ."bookYear='".$this->yearOptions->value."' ";
        }
        
        if($this->buildingOptions->enabled) {
            if($this->buildingOptions->operator != "") {
                $sql = $sql .$this->buildingOptions->operator." ";
            }
            $sql = $sql ."bookBuilding='".$this->buildingOptions->value."' ";
        }
        
        if($this->subjectOptions->enabled) {
            if($this->subjectOption->operator != "") {
                $sql = $sql .$this->subjectOption->operator." ";
            }
            $sql = $sql ."bookSubject='".$this->subjectOptions->value."' ";
        }
        
        if($sql === "") {
            return false;
        } else {
            return $sql;
        }
        
    }
}

class Database {
    private string $servername      = "127.0.0.1";
    private string $username        = "root";
    private string $password        = "";
    private string $databaseName    = "library";
    private $conn;
    
    public function __construct(){
        // Create connection
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->databaseName);

        if ($this->conn->connect_error) {
            $this->setupConnect();
        }

        if (!$this->conn->connect_error && $this->conn != null) {
            $sql = "select 1 from `books` LIMIT 1";
            if(!$this->conn->query($sql)){
                $this->setupBookTable();
            }
        }

        if (!$this->conn->connect_error && $this->conn != null) {
            $sql = "select 1 from `borrows` LIMIT 1";
            if(!$this->conn->query($sql)){
                $this->setupBorrowsTable();
            }
        }

        $this->disconnect();
    }

    // connection to the database
    public function connect(){
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->databaseName);

        $this->checkConnection();
    }

    // Connection withhout DB
    public function setupConnect() {
        $this->conn = new mysqli($this->servername, $this->username, $this->password);
        $this->setupDatabase();
    }

    // DISCONNECT
    public function disconnect(){
        if($this->checkConnection()){
            $this->conn->close();
        }
    }

    // SETUP $databaseName DATABASE
    public function setupDatabase(){
        // Create database $databaseName
        $sql = "CREATE DATABASE " . $this->databaseName;
        if ($this->conn->query($sql) === TRUE) {
            $this->connect();
        } else {
            print("Error creating database: " . $this->conn->error);
        }
    }

    // SETUP Books TABLE
    public function setupBookTable(){
        // sql to create books table
        $sql = "CREATE TABLE books (
        bookIsbn VARCHAR(50) PRIMARY KEY,
        bookTitle VARCHAR(50) NOT NULL,
        bookPublisher VARCHAR(50) NOT NULL,
        bookAuthor VARCHAR(50) NOT NULL,
        bookPrice FLOAT(50) NOT NULL,
        bookYear VARCHAR(50) NOT NULL,
        bookBand VARCHAR(50) NOT NULL,
        bookSeries VARCHAR(50) NOT NULL,
        bookSignature VARCHAR(50) NOT NULL,
        bookBuilding VARCHAR(50) NOT NULL,
        bookSubject VARCHAR(50) NOT NULL,
        bookSubjectSpecialisation VARCHAR(50) NOT NULL,
        bookAttachments VARCHAR(50) NOT NULL,
        bookBorrowAble BOOLEAN NOT NULL,
        bookAvaibleCopys INT(50) NOT NULL,
        bookBorrowedCopys INT(50) NOT NULL,
        reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        if ($this->conn->query($sql) === TRUE) {
            JavaUtil::toConsole("Table Books created successfully");
        } else {
            print("Error creating table: " . $this->conn->error);
        }
    }
    
    // SETUP Borrows TABLE
    public function setupBorrowsTable(){
        /*
        private string $teacher;
        private string $bookIsbn;
        private string $studentName;
        private string $studentClass;
        */

        // sql to create borrows table
        $sql = "CREATE TABLE borrows (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                borrowTeacher VARCHAR(50) NOT NULL,
                borrowBookIsbn VARCHAR(50) NOT NULL,
                borrowStudentName VARCHAR(50) NOT NULL,
                borrowStudentClass VARCHAR(50) NOT NULL,
                borrowStatus VARCHAR(50) NOT NULL,
                reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";

        if ($this->conn->query($sql) === TRUE) {
        } else {
            print("Error creating table: " . $this->conn->error);
        }
    }
    
    // CHECK CONNECTION
    public function checkConnection(){
        // Check connection
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        return true;
    }
    
    public function addBook(Book $book){
        $sql = "INSERT INTO books (
                bookIsbn,
                bookPublisher,
                bookTitle,
                bookAuthor,
                bookPrice,
                bookYear,
                bookBand,
                bookSeries,
                bookSignature,
                bookBuilding,
                bookSubject,
                bookSubjectSpecialisation,
                bookAttachments,
                bookBorrowAble,
                bookAvaibleCopys,
                bookBorrowedCopys)
                VALUES (
                '".$book->getIsbn()."', 
                '".$book->getBookPublisher()."', 
                '".$book->get_title()."',
                '".$book->getAuthor()."',  
                ".$book->getPrice().", 
                '".$book->getYear()."', 
                '".$book->getBand()."', 
                '".$book->getSeries()."', 
                '".$book->getSignature()."', 
                '".$book->getBuilding()."', 
                '".$book->getSubject()."', 
                '".$book->getSubjectSpecialisation()."', 
                '".$book->getAttachments()."', 
                ".intval($book->getBorrowAble()).", 
                ".$book->getAvaibleCopys().", 
                ".$book->getBorrowedCopys()."
                )";
        if ($this->conn->query($sql) === TRUE) {
            return true;
        } else {
            print("Error creating table: " . $this->conn->error);
            return false;
        }
    }

    public function getBook(string $isbn) {
        $sql = "SELECT * FROM books WHERE bookIsbn='".$isbn."'";
        $result = $this->conn->query($sql);
        if ($result) {
            $result = $result->fetch_assoc();
            return new Book(
                $result["bookIsbn"], 
                $result["bookPublisher"], 
                $result["bookTitle"], 
                $result["bookAuthor"],
                floatval($result["bookPrice"]), 
                $result["bookYear"],
                $result["bookBand"],
                $result["bookSeries"], 
                $result["bookSignature"],  
                $result["bookBuilding"], 
                $result["bookSubject"], 
                $result["bookSubjectSpecialisation"], 
                $result["bookAttachments"], 
                boolval($result["bookBorrowAble"]), 
                intval($result["bookAvaibleCopys"]), 
                intval($result["bookBorrowedCopys"]));
        } else {
            print("fehler beim buch finden");
            return false;
        }
    }

    public function getBooks(filterOptions $filterOptions) {
        if($filterOptions->buildSQLString() === false){
            return false;
        }

        $sql = "SELECT * FROM books WHERE " . $filterOptions->buildSQLString();
        $result = $this->conn->query($sql);
        $listOfBooks = [];
        if(!isset($result->num_rows)) {
            die;
        }
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                array_push($listOfBooks, new Book(
                    $row["bookIsbn"],
                    $row["bookPublisher"], 
                    $row["bookTitle"], 
                    $row["bookAuthor"],
                    floatval($row["bookPrice"]), 
                    $row["bookYear"],
                    $row["bookBand"],
                    $row["bookSeries"], 
                    $row["bookSignature"],  
                    $row["bookBuilding"], 
                    $row["bookSubject"], 
                    $row["bookSubjectSpecialisation"], 
                    $row["bookAttachments"], 
                    boolval($row["bookBorrowAble"]), 
                    intval($row["bookAvaibleCopys"]), 
                    intval($row["bookBorrowedCopys"])));
            }
            return $listOfBooks;
        }
        
        
    }

    public function updateBook(book $book) {
        $updateString = "bookIsbn='".$book->getIsbn()."', 
                        bookPublisher='".$book->getBookPublisher()."', 
                        bookTitle='".$book->get_title()."', 
                        bookAuthor='".$book->getAuthor()."', 
                        bookPrice=".$book->getPrice().", 
                        bookYear='".$book->getYear()."', 
                        bookBand='".$book->getBand()."',
                        bookSeries='".$book->getSeries()."', 
                        bookSignature='".$book->getSignature()."', 
                        bookBuilding='".$book->getBuilding()."', 
                        bookSubject='".$book->getSubject()."', 
                        bookSubjectSpecialisation='".$book->getSubjectSpecialisation()."', 
                        bookAttachments='".$book->getAttachments()."', 
                        bookBorrowAble=".intval($book->getBorrowAble()).", 
                        bookAvaibleCopys=".$book->getAvaibleCopys().", 
                        bookBorrowedCopys=".$book->getBorrowedCopys()." ";

        $sql = "UPDATE books SET ".$updateString." WHERE bookIsbn='".$book->getIsbn()."'";

        if ($this->conn->query($sql) === TRUE) {
            print("gj");
            return true;
        } else {
            print("Error creating table: " . $this->conn->error);
            return false;
        }
    }
    
    public function enougthExemplars($isbn){
        if($this->getBook($isbn)->getAvaibleCopys() > $this->getBook($isbn)->getBorrowedCopys()){
            return true;
        } else {
            return false;
        }
    }

    public function incrementBorrowCount($isbn){
        if(!$this->enougthExemplars($isbn)){
            return false;
        }

        $newBorrowVal = $this->getBook($isbn)->getBorrowedCopys() + 1;
        $sql = "UPDATE books SET bookBorrowedCopys='".$newBorrowVal."' WHERE bookIsbn='".$isbn."'";

        if ($this->conn->query($sql) === TRUE) {
            return true;
        } else {
            return false;
        }
    }

    public function deleteBorrowEntry($id){
        $sql = "DELETE FROM borrows WHERE id='".$id."'";

        if ($this->conn->query($sql) === TRUE) {
            return true;
          } else {
            return false;
          }
    }

    public function decrementBorrowCount($isbn){
        $borrowedCopys = $this->getBook($isbn)->getBorrowedCopys();
        if($borrowedCopys == 0) {
            return false;
        }

        $newBorrowVal = $this->getBook($isbn)->getBorrowedCopys() - 1;
        $sql = "UPDATE books SET bookBorrowedCopys='".$newBorrowVal."' WHERE bookIsbn='".$isbn."'";

        if ($this->conn->query($sql) === TRUE) {
            return true;
        } else {
            return false;
        }
    }
    
    public function getBorrowerInformation(int $id) {
        $sql = "SELECT * FROM borrows WHERE id='".$id."'";
        $result = $this->conn->query($sql);
        if ($result) {
            $result = $result->fetch_assoc();
            return new borrowInformations(
                $result["borrowTeacher"], 
                $result["borrowBookIsbn"], 
                $result["borrowStudentName"],
                $result["borrowStudentClass"],
                $result["borrowStatus"],
                intval($id)
            );
        } else {
            return false;
        }
    }

    public function deleteBook($isbn) {
        $sql = "DELETE FROM books WHERE bookIsbn='".$isbn."'";

        if ($this->conn->query($sql) === TRUE) {
            return true;
          } else {
            return false;
          }
    }

    public function getAllBorrowerInformation() {
        $sql = "SELECT * FROM borrows";
        $result = $this->conn->query($sql);
        $borrowerList = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                array_push($borrowerList,new borrowInformations(
                    $row["borrowTeacher"], 
                    $row["borrowBookIsbn"], 
                    $row["borrowStudentName"],
                    $row["borrowStudentClass"],
                    $row["borrowStatus"],
                    intval($row["id"])));
            }
            return $borrowerList;
        } else {
            return false;
        }
    }
    /*
        private string $teacher;
        private string $bookIsbn;
        private string $studentName;
        private string $studentClass;
    */
    public function borrowBook(borrowInformations $borrowInfo){
        $sql = "INSERT INTO borrows (
            borrowTeacher,
            borrowBookIsbn,
            borrowStudentName,
            borrowStudentClass, 
            borrowStatus)
            VALUES (
            '".$borrowInfo->getTeacher()."', 
            '".$borrowInfo->getBookIsbn()."',
            '".$borrowInfo->getStudentName()."',  
            '".$borrowInfo->getStudentClass()."',
            '".$borrowInfo->getBorrowStatus()."'
            )";
        if ($this->conn->query($sql) === TRUE) {
            $this->incrementBorrowCount($borrowInfo->getBookIsbn());
            return true;
        } else {
            print("Error creating table: " . $this->conn->error);
            return false;
        }
    }

    public function retrieveBook(int $id){
        $sql = "UPDATE borrows SET borrowStatus='RETRIEVED' WHERE id='".$id."'";

        if ($this->conn->query($sql) === TRUE) {
            $this->decrementBorrowCount($this->getBorrowerInformation($id)->getBookIsbn());
            return true;
        } else {
            print("Error creating table: " . $this->conn->error);
            return false;
        }
    }
}

/**
 * 
 */
class Book implements JsonSerializable {
    /**
     * ISBN Number of the Book.
     */
    private string $isbn;

    /**
     * Book Publisher
     */
    private string $bookPublisher;

    /**
     * Title of the Book.
     */
    private string $title;

    /**
     * Author of the Book.
     */
    private string $author;
    
    /**
     * Price of the Book.
     */
    private float $price;
    
    /**
     * Year of the Book.
     */
    private string $year;

    /**
     * Book Band.
     */
    private string $band;
    
    /**
     * Signature of the Book.
     */
    private string $signature;
    
    /**
     * Series of the Book.
     */
    private string $series;
    
    /**
     * Building where the Book is stored.
     */
    private string $building;
    
    /**
     * Subject of the Book.
     */
    private string $subject;
    
    /**
     * Subject Specialisation of the Book.
     */
    private string $subjectSpecialisation;
    
    /**
     * Attachments to the Book.
     */
    private string $attachments;
    
    /**
     * Is the Book available?
     * TRUE or FALSE.
     */
    private bool $borrowAble;
    
    /**
     * How much Copys of this Book are available?
     */
    private int $avaibleCopys;

    /**
     * How many Copys of this Books are currently borrowed?
     */
    private int $borrowedCopys;

    /**
     * Constructor of Book
     * @param string $isbn
     * @param string title
     * @param float $price
     * @param string $year
     * @param string $signature
     * @param string $series
     * @param string $building
     * @param string $subject
     * @param string $subjectSpecialisation
     * @param string $attachments
     * @param bool $borrowAble
     * @param int $avaibleCopys
     * @param int $borrowedCopys
     */
    public function __construct(
        string $isbn,
        string $bookPublisher,
        string $title,
        string $author,
        float $price,
        string $year,
        string $band,
        string $signature,
        string $series,
        string $building,
        string $subject,
        string $subjectSpecialisation,
        string $attachments,
        bool $borrowAble,
        int $avaibleCopys,
        int $borrowedCopys) {
        $this->isbn=$isbn;
        $this->bookPublisher=$bookPublisher;
        $this->title=$title;
        $this->author=$author;
        $this->borrowAble=$borrowAble;
        $this->avaibleCopys=$avaibleCopys;
        $this->borrowedCopys=$borrowedCopys;
        $this->price=$price;
        $this->year=$year;
        $this->band=$band;
        $this->signature=$signature;
        $this->series=$series;
        $this->building=$building;
        $this->subject=$subject;
        $this->subjectSpecialisation=$subjectSpecialisation;
        $this->attachments=$attachments;

    }

    public function get_isbn() {
        return $this->isbn;
    }

    public function get_title() {
        return $this->title;
    }

    public function jsonSerialize() {
        return [ 'isbn'=>$this->isbn,
        'title'=>$this->title,
        'author'=>$this->author,
        'borrowAble'=>$this->borrowAble,
        'avaibleCopys'=>$this->avaibleCopys,
        'borrowedCopys'=>$this->borrowedCopys,
        'price'=>$this->price,
        'year'=>$this->year,
        'signature'=>$this->signature,
        'series'=>$this->series,
        'building'=>$this->building,
        'subject'=>$this->subject,
        'subjectSpecialisation'=>$this->subjectSpecialisation,
        'attachments'=>$this->attachments,
        ];
    }

    /**
     * Get how many copies of this Books are currently borrowed ?
     */ 
    public function getBorrowedCopys()
    {
        return $this->borrowedCopys;
    }

    /**
     * Set how many copies of this Books are currently borrowed ?
     *
     * @return  self
     */ 
    public function setBorrowedCopys($borrowedCopys)
    {
        $this->borrowedCopys = $borrowedCopys;

        return $this;
    }

    /**
     * Get how many Books are available ?.
     */ 
    public function getAvaibleCopys()
    {
        return $this->avaibleCopys;
    }

    /**
     * Set how many copies of this Book are available ?
     *
     * @return  self
     */ 
    public function setAvaibleCopys($avaibleCopys)
    {
        $this->avaibleCopys = $avaibleCopys;

        return $this;
    }

    /**
     * Get is the Book available ?
     */ 
    public function getBorrowAble()
    {
        return $this->borrowAble;
    }

    /**
     * Set is the Book available ?
     *
     * @return  self
     */ 
    public function setBorrowAble($borrowAble)
    {
        $this->borrowAble = $borrowAble;

        return $this;
    }

    /**
     * Get attachments to the Book.
     */ 
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Set attachments to the Book.
     *
     * @return  self
     */ 
    public function setAttachments($attachments)
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Get subject Specialisation of the Book.
     */ 
    public function getSubjectSpecialisation()
    {
        return $this->subjectSpecialisation;
    }

    /**
     * Set subject Specialisation of the Book.
     *
     * @return  self
     */ 
    public function setSubjectSpecialisation($subjectSpecialisation)
    {
        $this->subjectSpecialisation = $subjectSpecialisation;

        return $this;
    }

    /**
     * Get subject of the Book.
     */ 
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set subject of the Book.
     *
     * @return  self
     */ 
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get building where the Book is stored.
     */ 
    public function getBuilding()
    {
        return $this->building;
    }

    /**
     * Set building where the Book is stored.
     *
     * @return  self
     */ 
    public function setBuilding($building)
    {
        $this->building = $building;

        return $this;
    }

    /**
     * Get series of the Book.
     */ 
    public function getSeries()
    {
        return $this->series;
    }

    /**
     * Set series of the Book.
     *
     * @return  self
     */ 
    public function setSeries($series)
    {
        $this->series = $series;

        return $this;
    }

    /**
     * Get signature of the Book.
     */ 
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Set signature of the Book.
     *
     * @return  self
     */ 
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get year of the Book.
     */ 
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set year of the Book.
     *
     * @return  self
     */ 
    public function setYear($year)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get price of the Book.
     */ 
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set price of the Book.
     *
     * @return  self
     */ 
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get author of the Book.
     */ 
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set author of the Book.
     *
     * @return  self
     */ 
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get title of the Book.
     */ 
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title of the Book.
     *
     * @return  self
     */ 
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get iSBN Number of the Book.
     */ 
    public function getIsbn()
    {
        return $this->isbn;
    }

    /**
     * Set iSBN Number of the Book.
     *
     * @return  self
     */ 
    public function setIsbn($isbn)
    {
        $this->isbn = $isbn;

        return $this;
    }

    /**
     * Get book Publisher
     */ 
    public function getBookPublisher()
    {
        return $this->bookPublisher;
    }

    /**
     * Set book Publisher
     *
     * @return  self
     */ 
    public function setBookPublisher($bookPublisher)
    {
        $this->bookPublisher = $bookPublisher;

        return $this;
    }

    /**
     * Get book Band.
     */ 
    public function getBand()
    {
        return $this->band;
    }

    /**
     * Set book Band.
     *
     * @return  self
     */ 
    public function setBand($band)
    {
        $this->band = $band;

        return $this;
    }
}


class borrowInformations {
    /**
     * ID
     */
    private int $id;

    /**
     * Teacher takes - entry into the System
     */
    private string $teacher;

    /**
     * The Borrowed book - ISBN Number
     */
    private string $bookIsbn;

    /**
     * Name of Student
     */
    private string $studentName;

    /**
     * Class of Student
     */
    private string $studentClass;

    /**
     * Status
     */
    private string $borrowStatus;

    public function __construct($teacher, $bookIsbn, $studentName, $studentClass, $borrowStatus, $id=-0){
        $this->teacher = $teacher;
        $this->bookIsbn = $bookIsbn;
        $this->studentName = $studentName;
        $this->studentClass = $studentClass;
        $this->borrowStatus = $borrowStatus;
        $this->id = $id;
    }

    /**
     * Get Teacher takes - entry into the System
     */ 
    public function getTeacher()
    {
        return $this->teacher;
    }

    /**
     * Set Teacher takes - entry into the System
     *
     * @return  self
     */ 
    public function setTeacher($teacher)
    {
        $this->teacher = $teacher;

        return $this;
    }

    /**
     * Get the Borrowed book ISBN Number
     */ 
    public function getBookIsbn()
    {
        return $this->bookIsbn;
    }

    /**
     * Set the Borrowed book ISBN Number
     *
     * @return  self
     */ 
    public function setBookIsbn($bookIsbn)
    {
        $this->bookIsbn = $bookIsbn;

        return $this;
    }

    /**
     * Get name Student
     */ 
    public function getStudentName()
    {
        return $this->studentName;
    }

    /**
     * Set name Student
     *
     * @return  self
     */ 
    public function setStudentName($studentName)
    {
        $this->studentName = $studentName;

        return $this;
    }

    /**
     * Get class Student
     */ 
    public function getStudentClass()
    {
        return $this->studentClass;
    }

    /**
     * Set class Student
     *
     * @return  self
     */ 
    public function setStudentClass($studentClass)
    {
        $this->studentClass = $studentClass;

        return $this;
    }

    /**
     * Get status
     */ 
    public function getBorrowStatus()
    {
        return $this->borrowStatus;
    }

    /**
     * Set status
     *
     * @return  self
     */ 
    public function setBorrowStatus($borrowStatus)
    {
        $this->borrowStatus = $borrowStatus;

        return $this;
    }

    /**
     * Get iD
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set iD
     *
     * @return  self
     */ 
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}

class componentChanger {
    private int $currentState;
    private formGenerator $BookFormObservable;//Buch zur Bibliothek hinzufügen. Bücher suche. Ausgeliehene Bücher.
    private string $caption = "<img class='logo' src='./LeoStatzLogo.png'/>";
    private string $navi = "<form class='nav' method='post' action='library.php'>
                                <button class='addBook' name='changeToAddBook' type='submit'><img src='https://img.icons8.com/dotty/80/000000/add-book.png'/><h1>Hinzufügen</h1></button>
                                <button class='searchBook' name='changeToSearchBook' type='submit'><img src='https://img.icons8.com/pastel-glyph/80/000000/search--v2.png'/><h1>Suchen</h1></button>
                                <button class='borrowList' name='changeToBorrowBook' type='submit'><img style='width: 80px;margin-top:1.5px;' src='https://img.icons8.com/wired/100/000000/borrow-book.png'/><h1>Ausgeliehen</h1></button>
                            </form>";

    public function changeToAddBook() {
        if($this->currentState == 0) { return; }
        
        ob_end_clean();
        ob_start();

        echo $this->caption;
        echo $this->navi;
        $this->BookFormObservable->genAddBookForm();

        $this->currentState = 0;
    }

    public function changeToSearchBook(){
        if($this->currentState == 1) { return; }

        ob_end_clean();
        ob_start();

        echo $this->caption;
        echo $this->navi;

        $this->BookFormObservable->genSearchBooksForm();

        $this->currentState = 1;
    }

    public function changeToBorrowBook(){
        if($this->currentState == 2) { return; }

        ob_end_clean();
        ob_start();

        echo $this->caption;
        echo $this->navi;

        $this->BookFormObservable->borrowerList();

        $this->currentState = 2;
    }

    public function changeToBorrowBookForm($isbn){

        ob_end_clean();
        ob_start();

        echo $this->caption;
        echo $this->navi;

        $this->BookFormObservable->borrowForm($isbn);

    }

    public function changeToEditBook(book $book){
        ob_end_clean();
        ob_start();

        echo $this->caption;
        echo $this->navi;

        $this->BookFormObservable->editBookForm($book);
    }

    public function changeToGenPDF(string $isbn){
        ob_end_clean();
        ob_start();

        echo $this->caption;
        echo $this->navi;

        $this->BookFormObservable->GenPDFForm($isbn);
    }

    public function searchTable(array $listOfBooks){
        if($this->currentState == 3) { return; }

        ob_end_clean();
        ob_start();

        echo $this->caption;
        echo $this->navi;   

        $this->BookFormObservable->genSearchTable($listOfBooks);

        $this->currentState = 3;
    }

    public function __construct(){
        ob_start();
        $this->BookFormObservable = new formGenerator();
        $this->currentState = -1;
        $this->changeToAddBook();
    }
}

class formGenerator {

    public function genAddBookForm(){
        $form = "<div><form method='post' action='library.php'><h2 class='caption'>Herzlich Willkommen auf der Lehrerbibliothek des Leo-Statz-Berufskollegs.</h2><hr class='hr' /><h3 class='tableHeader' align='center'>Buch zur Bibliothek hinzufügen:</h3><table align='center' class='table'>
            <tr>
                <td><label for='author'>Author/in: </label>
                <td><input style='width:100%' type='text' id='author' name='author'></input></td>
            </tr>
            
            <tr>
                <td><label for='title'>Title: </label>
                <td><input style='width:100%' type='text' id='title' name='title'></input></td>
            </tr>

            <tr>
                <td><label for='publisher'>Verlag: </label>
                <td><input style='width:100%' type='text' id='publisher' name='publisher'></input></td>
            </tr>

            <tr>
                <td><label for='isbn'>ISBN: </label>
                <td><input style='width:100%' type='text' id='isbn' name='isbn'></input></td>
            </tr>

            <tr>
                <td><label for='year'>Jahr: </label>
                <td><input style='width:100%' type='text' id='year' name='year'></input></td>
            </tr>

            <tr>
                <td><label for='band'>Band: </label>
                <td><input style='width:100%' type='text' id='band' name='band'></input></td>
            </tr>

            <tr>
                <td><label for='signature'>Signatur: </label>
                <td><input style='width:100%' type='text' id='signature' name='signature'></input></td>
            </tr>

            <tr>
                <td><label for='series'>Auflage: </label>
                <td><input style='width:100%' type='text' id='series' name='series'></input></td>
            </tr>

            <tr>
                <td><label for='avaibleBooksCount'>Verfügbare Exemplare: </label>
                <td><input style='width:100%' type='text' id='avaibleBooksCount' name='avaibleBooksCount'></input></td>
            </tr>

            <tr>
                <td><label for='building'>Schulgebäude: </label>
                <td><select style='width:100%' name='building' id='building'>
                    <option value=''></option>
                    <option value='K'>K</option>
                    <option value='F'>F</option>
                </select></td>
            </tr>
            <tr>
                <td><label for='subject'>Fachbereiche: </label>
                <td><select style='width:100%' name='subject' id='subject'>
                    <option value=''></option>
                    <option value='Banken'>Banken</option>
                    <option value='Arbeitsmarktdienstleistungen'>Arbeitsmarktdienstleistungen</option>
                    <option value='Ausbildungsvorbereitung'>Ausbildungsvorbereitung</option>
                    <option value='Büromanagement'>Büromanagement</option>
                    <option value='Privatversicherung'>Privatversicherung</option>
                    <option value='Personaldienstleistung'>Personaldienstleistung</option>
                    <option value='Sozialversicherung'>Sozialversicherung</option>
                    <option value='Kaufmännische Assistenten für Informationsverarbeitung'>Kfm. Assistenten für Informationsverarbeitung</option>
                    <option value='Höhere Handelsschule'>Höhere Handelsschule</option>
                    <option value='Handelsschule'>Handelsschule</option>
                </select></td>
            </tr>
            <tr>
                <td><label for='subjectSpecialisation'>Fachrichtung: </label>
                <td><select style='width:100%' name='subjectSpecialisation' id='subjectSpecialisation'>
                    <option value=''></option>
                    <option value='AWL'>AWL</option>
                    <option value='Deutsch'>Deutsch</option>
                    <option value='Englisch'>Englisch</option>
                    <option value='Politik'>Politik</option>
                    <option value='Geografie'>Geografie</option>
                    <option value='Pädagogik'>Pädagogik</option>
                    <option value='Spanisch'>Spanisch</option>
                    <option value='Personaldienstleistung'>Personaldienstleistung</option>
                    <option value='Sozialversicherung'>Sozialversicherung</option>
                    <option value='Banken'>Banken</option>
                </select></td>
            </tr>
            <tr>
                <td><label for='price'>Preis: </label>
                <td><input style='width:100%' type='text' id='price' name='price'></input></td>
            </tr>

            <tr>
                <td><label for='attachments'>Anlagen: </label>
                <td><input style='width:100%' type='text' id='attachments' name='attachments'></input></td>
            </tr>
            <tr>
                <td><label for='borrowAble'>Ausleihbar: </label>
                <td><select style='width:100%' name='borrowAble' id='borrowAble'>
                    <option value=true>Ja</option>
                    <option value=false>Nein</option>
                </select></td>
            </tr>
            <tr><td colspan='2'><button style='width:100%' name='addBookButton' type='submit'>Buch hinzufügen.</button></td></tr>
        </form></div>";

        echo $form;
    }

    public function genSearchBooksForm(){
        $form = 
        "<h2 class='caption'>Herzlich Willkommen auf den Seiten der gemeinsamen Lehrerbibliothek des Leo-Statz-Berufskollegs.</h2><hr class='hr' /><h3 class='tableHeader' align='center'>Buch in der Bibliothek suchen:</h3><form method='post' action='library.php'><table align='center' class='table'>
            <tr>
                <td><select name='authorOption' id='authorOption'>
                    <option value='AND'></option>
                    <option value='AND'>und</option>
                    <option value='OR'>oder</option>
                </select></td>
                <td><label for='author'>Author/in: </label></td>
                <td><input style='width:100%' type='text' id='author' name='author'></input></td>
            </tr>
            <tr>
                <td><select name='isbnOption' id='isbnOption'>
                    <option value='AND'></option>
                    <option value='AND'>und</option>
                    <option value='OR'>oder</option>
                </select></td>
                <td><label for='isbn'>ISBN: </label></td>
                <td><input style='width:100%' type='text' id='isbn' name='isbn'></input></td>
            </tr>
            <tr>
                <td><select name='yearOption' id='yearOption'>
                    <option value='AND'></option>
                    <option value='AND'>und</option>
                    <option value='OR'>oder</option>
                </select></td>
                <td><label for='year'>Jahr: </label></td>
                <td><input style='width:100%' type='text' id='year' name='year'></input></td>
            </tr>
            <tr>
                <td><select name='buildingOption' id='buildingOption'>
                    <option value='AND'></option>
                    <option value='AND'>und</option>
                    <option value='OR'>oder</option>
                </select></td>
                <td><label for='building'>Schulgebäude: </label></td>
                <td><select style='width:100%' name='building' id='building'>
                        <option value=''></option>
                        <option value='K'>K</option>
                        <option value='F'>F</option>
                    </select></td>
            </tr>
            <tr>
                <td><select name='subjectOption' id='subjectOption'>
                    <option value='AND'></option>
                    <option value='AND'>und</option>
                    <option value='OR'>oder</option>
                </select></td>
                <td><label for='subject'>Fachbereiche: </label></td>
                <td><select style='width:100%' name='subject' id='subject'>
                        <option value=''></option>
                        <option value='Banken'>Banken</option>
                        <option value='Arbeitsmarktdienstleistungen'>Arbeitsmarktdienstleistungen</option>
                        <option value='Ausbildungsvorbereitung'>Ausbildungsvorbereitung</option>
                        <option value='Büromanagement'>Büromanagement</option>
                        <option value='Privatversicherung'>Privatversicherung</option>
                        <option value='Personaldienstleistung'>Personaldienstleistung</option>
                        <option value='Sozialversicherung'>Sozialversicherung</option>
                        <option value='Kaufmännische Assistenten für Informationsverarbeitung'>Kfm. Assistenten für Informationsverarbeitung</option>
                        <option value='Höhere Handelsschule'>Höhere Handelsschule</option>
                        <option value='Handelsschule'>Handelsschule</option>
                </select></td>
            </tr>

            <tr>
                <td colspan='3'><button style='width:100%' name='searchBookButton' type='submit'>Nach dem Buch suchen.</button></td>
            </tr>
        </table></form>";

        echo $form;
    }

    public function genSearchTable(array $listOfBooks) {
        $table = "<h2 class='caption'>Herzlich Willkommen auf den Seiten der gemeinsamen Lehrerbibliothek des Leo-Statz-Berufskollegs.</h2><hr class='hr' /><h3 class='tableHeader' align='center'>Gefundene Bücher:</h3><form method='post' action='library.php'><table align='center' class='table'>
        <tr>
            <th>Autor/in</th>
            <th>Titel</th>
            <th>Verlag</th>
            <th>ISBN</th>
            <th>Erscheinungs Jahr</th>
            <th>Band</th>
            <th>Gebäude</th>
            <th>Fachbereiche</th>
            <th>Fachrichtung</th>
            <th>Signatur</th>
            <th>Auflage</th>
            <th>Exemplare</th>
            <th>Derzeit ausgeliehen</th>
            <th>Preis</th>
            <th>Anhang</th>
            <th>Ausleihbar</th>
            <th>Bearbeiten</th>
            <th>Ausleihen</th>
            <th>PDF Generieren</th>
        </tr>";
        foreach ($listOfBooks as $book) {
            $borrowAble = $book->getBorrowAble() ? "Ja" : "Nein";
            $table = $table . "
            <tr>
                <td align='center'>".$book->getAuthor()."</td>
                <td align='center'>".$book->getTitle()."</td>
                <td align='center'>".$book->getBookPublisher()."</td>
                <td align='center'>".$book->getIsbn()."</td>
                <td align='center'>".$book->getYear()."</td>
                <td align='center'>".$book->getBand()."</td>
                <td align='center'>".$book->getBuilding()."</td>
                <td align='center'>".$book->getSubject()."</td>
                <td align='center'>".$book->getSubjectSpecialisation()."</td>
                <td align='center'>".$book->getSignature()."</td>
                <td align='center'>".$book->getSeries()."</td>
                <td align='center'>".$book->getAvaibleCopys()."</td>
                <td align='center'>".$book->getBorrowedCopys()."</td>
                <td align='center'>".$book->getPrice()."</td>
                <td align='center'>".$book->getAttachments()."</td>
                <td align='center'>".$borrowAble."</td>
                <td><form method='post' action='library.php'><input style='display:none; heigth:0px; width:0px;' id='isbn' name='isbn' value='".$book->getIsbn()."'></input><button type='submit' name='changeToEditBook'>Bearbeiten</button></form></td>
                <td><form method='post' action='library.php'><input style='display:none; heigth:0px; width:0px;' id='isbn' name='isbn' value='".$book->getIsbn()."'></input><button type='submit' name='borrowBook'>Ausleihen</button></form></td>
                <td><form method='post' action='library.php'><input style='display:none; heigth:0px; width:0px;' id='isbn' name='isbn' value='".$book->getIsbn()."'></input><button type='submit' name='changeToGenPDF'>PDF Generieren</button></form></td>
            </tr>";
        }
        $table = $table . "</table>";

        echo $table;
    }

    public function borrowerList() { // NO ENTRY = ERROR ?
        $DBA = new Database();
        $DBA->connect();
        $listOfBorrower = $DBA->getAllBorrowerInformation();
        $table = "<h2 class='caption'>Herzlich Willkommen der Lehrerbibliothek des Leo-Statz-Berufskollegs.</h2><hr class='hr' /><h3 class='tableHeader' align='center'>Liste der Leihungen:</h3><form method='post' action='library.php'><table align='center' class='table'>";
        if($listOfBorrower != false) {
            $table = $table ."<tr>
                <th>Buch ISBN</th>
                <th>Buch Titel</th>
                <th>Name des Lehrers</th>
                <th>Name des Schülers</th>
                <th>Klasse des Schülers</th>
                <th>Ausleih Status</th>
                <th>Zurückgeben</th>
            </tr>";
            foreach ($listOfBorrower as $borrower) {
                if($listOfBorrower)
                //var_dump($book);
                $retrieveButton = "";
                if($borrower->getBorrowStatus() != "RETRIEVED") {
                    $borrowStatus = "Noch ausgeliehen.";
                    $retrieveButton = "<form method='post' action='library.php'><input style='display:none' id='id' name='id' value='".$borrower->getId()."'></input><button type='submit' name='retrieveBook'>Zurückgeben.</button></form>";
                } else {
                    $retrieveButton = "<form method='post' action='library.php'><input style='display:none' id='id' name='id' value='".$borrower->getId()."'></input><button type='submit' name='deleteBorrowEntry'>Eintrag löschen.</button></form>";
                    $borrowStatus = "Zurückgegeben.";
                }
    
                $table = $table . "
                <tr>
                    <td align='center'>".$borrower->getBookIsbn()."</td>
                    <td align='center'>".$DBA->getBook($borrower->getBookIsbn())->getTitle()."</td>
                    <td align='center'>".$borrower->getTeacher()."</td>
                    <td align='center'>".$borrower->getStudentName()."</td>
                    <td align='center'>".$borrower->getStudentClass()."</td>
                    <td align='center'>".$borrowStatus."</td>
                    <td align='center'>".$retrieveButton."</td>
                    <td></td>
                </tr>";
            }
        } else {
            $table = $table . "
                <tr>
                    <td align='center' colspan=7> Keine Daten über ausgeliehene Bücher vorhanden! </td>
                    <td></td>
                </tr>";
        }
        $table = $table . "</table>";

        echo $table;
        $DBA->disconnect();
    }

    public function editBookForm(book $book){
        $form = "<h2 class='caption'>Herzlich Willkommen auf der Lehrerbibliothek des Leo-Statz-Berufskollegs.</h2><hr class='hr' /><div><form method='post' action='library.php'><h3 class='tableHeader' align='center'>Buch in der Bibliothek aktualisieren:</h3><table align='center' class='table'>
            <tr>
                <td><label for='author'>Author/in: </label>
                <td><input style='width:100%' type='text' id='author' name='author' value='".$book->getAuthor()."' ></input></td>
            </tr>
            
            <tr>
                <td><label for='title'>Title: </label>
                <td><input style='width:100%' type='text' id='title' name='title' value='".$book->getTitle()."'></input></td>
            </tr>

            <tr>
                <td><label for='publisher'>Verlag: </label>
                <td><input style='width:100%' type='text' id='publisher' name='publisher' value='".$book->getBookPublisher()."'></input></td>
            </tr>

            <tr>
                <td><label for='isbn'>ISBN: </label>
                <td><input style='width:100%' type='text' id='isbn' name='isbn' value='".$book->getIsbn()."'></input></td>
            </tr>

            <tr>
                <td><label for='year'>Jahr: </label>
                <td><input style='width:100%' type='text' id='year' name='year' value='".$book->getYear()."'></input></td>
            </tr>

            <tr>
                <td><label for='band'>Band: </label>
                <td><input style='width:100%' type='text' id='band' name='band' value='".$book->getBand()."'></input></td>
            </tr>

            <tr>
                <td><label for='signature'>Signatur: </label>
                <td><input style='width:100%' type='text' id='signature' name='signature' value='".$book->getSignature()."'></input></td>
            </tr>

            <tr>
                <td><label for='series'>Auflage: </label>
                <td><input style='width:100%' type='text' id='series' name='series' value='".$book->getSeries()."'></input></td>
            </tr>

            <tr>
                <td><label for='avaibleBooksCount'>Verfügbare Exemplare: </label>
                <td><input style='width:100%' type='text' id='avaibleBooksCount' name='avaibleBooksCount' value='".$book->getAvaibleCopys()."'></input></td>
            </tr>

            <tr>
                <td><label for='building'>Schulgebäude: </label>
                <td><select style='width:100%' name='building' id='building' value='".$book->getBuilding()."'>
                    <option value='K'>K</option>
                    <option value='F'>F</option>
                </select></td>
            </tr>
            <tr>
                <td><label for='subject'>Fachbereiche: </label>
                <td><select style='width:100%' name='subject' id='subject' value='".$book->getSubject()."'>
                    <option value='Banken'>Banken</option>
                    <option value='Arbeitsmarktdienstleistungen'>Arbeitsmarktdienstleistungen</option>
                    <option value='Ausbildungsvorbereitung'>Ausbildungsvorbereitung</option>
                    <option value='Büromanagement'>Büromanagement</option>
                    <option value='Privatversicherung'>Privatversicherung</option>
                    <option value='Personaldienstleistung'>Personaldienstleistung</option>
                    <option value='Sozialversicherung'>Sozialversicherung</option>
                    <option value='Kaufmännische Assistenten für Informationsverarbeitung'>Kfm. Assistenten für Informationsverarbeitung</option>
                    <option value='Höhere Handelsschule'>Höhere Handelsschule</option>
                    <option value='Handelsschule'>Handelsschule</option>
                </select></td>
            </tr>
            <tr>
                <td><label for='subjectSpecialisation'>Fachrichtung: </label>
                <td><select style='width:100%' name='subjectSpecialisation' id='subjectSpecialisation' value='".$book->getSubjectSpecialisation()."'>
                    <option value='AWL'>AWL</option>
                    <option value='Deutsch'>Deutsch</option>
                    <option value='Englisch'>Englisch</option>
                    <option value='Politik'>Politik</option>
                    <option value='Geografie'>Geografie</option>
                    <option value='Pädagogik'>Pädagogik</option>
                    <option value='Spanisch'>Spanisch</option>
                    <option value='Personaldienstleistung'>Personaldienstleistung</option>
                    <option value='Sozialversicherung'>Sozialversicherung</option>
                    <option value='Banken'>Banken</option>
                </select></td>
            </tr>
            <tr>
                <td><label for='price'>Preis: </label>
                <td><input style='width:100%' type='text' id='price' name='price' value='".$book->getPrice()."'></input></td>
            </tr>

            <tr>
                <td><label for='attachments'>Anlagen: </label>
                <td><input style='width:100%' type='text' id='attachments' name='attachments' value='".$book->getAttachments()."'></input></td>
            </tr>
            <tr>
                <td><label for='borrowAble'>Ausleihbar: </label>
                <td><select style='width:100%' name='borrowAble' id='borrowAble' value='".$book->getBorrowAble()."'>
                    <option value=true>Ja</option>
                    <option value=false>Nein</option>
                </select></td>
            </tr>
            
            <input style='display:none;' type='text' id='bookBorrowedCopys' name='bookBorrowedCopys' value='".$book->getBorrowedCopys()."'></input>
            <tr><td><button style='width:100%' name='deleteBook' type='submit'>Buch löschen.</button></td><td><button style='width:100%' name='updateBook' type='submit'>Buch aktualisieren.</button></td></tr>
        </form></div>";

        echo $form;
    }

    public function borrowForm($bookIsbn){
        $form = "<h2 class='caption'>Herzlich Willkommen auf den Seiten der gemeinsamen Lehrerbibliothek des Leo-Statz-Berufskollegs.</h2><hr class='hr' /><h3 class='tableHeader' align='center'>Informationen zum ausleih vorgang:</h3><form method='post' action='library.php'><form method='post' action='library.php'><table align='center' class='table'>
                <tr>
                    <td><label for='teacher'>Lehrer: </label></td>
                    <td><input type='text' id='teacher' name='teacher'></input></td>
                </tr>
                <tr>
                    <td><label for='studentName'>Schüler Name: </label>
                    <td><input type='text' id='studentName' name='studentName'></input></td>
                </tr>
                <tr>
                    <td><label for='studentClass'>Schüler Klasse: </label>
                    <td><input type='text' id='studentClass' name='studentClass'></input></td>
                </tr>
                <tr>
                    <td><input style='display:none' type='text' id='bookIsbn' name='bookIsbn' value=".$bookIsbn."></input></td>
                    <td><button name='confirmBorrow' type='submit'>Submit</button></td>
                </tr>
            </table></form>";

        echo $form;
    }

    public function GenPDFForm($isbn){
        $form = "<h2 class='caption'>Herzlich Willkommen auf den Seiten der gemeinsamen Lehrerbibliothek des Leo-Statz-Berufskollegs.</h2><hr class='hr' /><h3 class='tableHeader' align='center'>Informationen zum ausleih vorgang:</h3><form method='post' action='library.php'><form method='post' action='library.php'><table align='center' class='table'>
                <tr>
                    <td><label for='stickerCount'>Wie viele Buch Sticker möchtest du haben ? </label></td>
                    <td><input type='text' id='stickerCount' name='stickerCount'></input></td>
                </tr>
                <tr>
                    <td><input style='display:none' type='text' id='isbn' name='isbn' value=".$isbn."></input></td>
                    <td><button name='genPDF' type='submit'>Submit</button></td>
                </tr>
            </table></form>";
        
        echo $form;
    }
}



$DB = new Database();
$componentChanger = new componentChanger;

if(isset($_POST['addBookButton'])){
    $book = new Book($_POST['isbn'],
    $_POST['publisher'],
    $_POST['title'], 
    $_POST['author'], 
    floatval($_POST['price']), 
    $_POST['year'], 
    $_POST['band'], 
    $_POST['series'], 
    $_POST['signature'], 
    $_POST['building'], 
    $_POST['subject'], 
    $_POST['subjectSpecialisation'],
    $_POST['attachments'],
    boolval($_POST['borrowAble']),
    intval($_POST['avaibleBooksCount']),
    0);

    $DB->connect();
    // Add Book to Library
    if($DB->addBook($book)){
        echo "<script>alert('Buch erfolgreich hinzugefügt !')</script>";
    }

    $DB->disconnect();
}

if(isset($_POST['searchBookButton'])){
    $filterOptions = new filterOptions(
        new filterItem($_POST['author']     ? true : false, $_POST['authorOption']      == ""   ? $_POST['authorOption']    : "",   $_POST['author']),
        new filterItem($_POST['isbn']       ? true : false, $_POST['isbnOption']        == ""   ? $_POST['isbnOption']      : "",   $_POST['isbn']),
        new filterItem($_POST['year']       ? true : false, $_POST['yearOption']        == ""   ? $_POST['yearOption']      : "",   $_POST['year']),
        new filterItem($_POST['building']   ? true : false, $_POST['buildingOption']    == ""   ? $_POST['buildingOption']  : "",   $_POST['building']),
        new filterItem($_POST['subject']    ? true : false, $_POST['subjectOption']     == ""   ? $_POST['subjectOption']   : "",   $_POST['subject'])
    );

    $DB->connect();
    // Add Book to Library
    $result = $DB->getBooks($filterOptions);
    if($result){
        $componentChanger->searchTable($result);
    } else {
        echo "<script>alert('Keine Bücher gefunden !')</script>";
    }

    $DB->disconnect();
}

if(isset($_POST['borrowBook'])){
    $componentChanger->changeToBorrowBookForm($_POST['isbn']);
}

if(isset($_POST['confirmBorrow'])){
    $borrowInfos = new borrowInformations(
        $_POST['teacher'],
        $_POST['bookIsbn'],
        $_POST['studentName'],
        $_POST['studentClass'],
        "ACTIVE");
    $DB->connect();
    // Add Book to Library
    if($DB->borrowBook($borrowInfos)){
        echo "<script>alert('Buch erfolgreich ausgeliehen !')</script>";
    };

    $DB->disconnect();
}

if(isset($_POST['retrieveBook'])){
    $DB->connect();
    if($DB->retrieveBook(intval($_POST['id']))){
        echo "<script>alert('Buch erfolgreich zurückgegeben !')</script>";
    }
    $DB->disconnect();
}

// changeToAddBook
if(isset($_POST['changeToAddBook'])){
    $componentChanger->changeToAddBook();
}

if(isset($_POST['updateBook'])){
    $book = new Book($_POST['isbn'],
    $_POST['publisher'],
    $_POST['title'], 
    $_POST['author'], 
    floatval($_POST['price']), 
    $_POST['year'], 
    $_POST['band'], 
    $_POST['series'], 
    $_POST['signature'], 
    $_POST['building'], 
    $_POST['subject'], 
    $_POST['subjectSpecialisation'],
    $_POST['attachments'],
    boolval($_POST['borrowAble']),
    intval($_POST['avaibleBooksCount']),
    intval($_POST['bookBorrowedCopys']));
    
    $DB->connect();
    // Add Book to Library
    if($DB->updateBook($book)){
        echo "<script>alert('Buch erfolgreich aktualisiert !')</script>";
    }

    $DB->disconnect();
}

// changeToAddBook
if(isset($_POST['changeToEditBook'])){
    $DB->connect();
    $book = $DB->getBook($_POST['isbn']);
    $componentChanger->changeToEditBook($book);

    $DB->disconnect();
}

// changeToAddBook
if(isset($_POST['deleteBook'])){
    $DB->connect();
    if($DB->deleteBook($_POST['isbn'])){
        echo "<script>alert('Buch erfolgreich gelöscht !')</script>";
    }

    $DB->disconnect();
}

// changeToAddBook
if(isset($_POST['deleteBorrowEntry'])){
    $DB->connect();
    if($DB->deleteBorrowEntry($_POST['id'])){
        echo "<script>alert('Eintrag erfolgreich gelöscht !')</script>";
    }

    $DB->disconnect();
}

// changeToSearchBook
if(isset($_POST['changeToSearchBook'])){
    $componentChanger->changeToSearchBook();
}

// changeToBorrowBook
if(isset($_POST['changeToBorrowBook'])){
    $componentChanger->changeToBorrowBook();
}

// changeToBorrowBook
if(isset($_POST['changeToGenPDF'])){
    $componentChanger->changeToGenPDF($_POST['isbn']);
}

// changeToBorrowBook
if(isset($_POST['genPDF'])){

    
    
    if($_POST['stickerCount'] != 0) {
        $DB->connect();
        $book = $DB->getBook($_POST['isbn']);
    
        echo '<script>window.location.href = "genPDF.php?sticker_count='.  $_POST['stickerCount'] .'&building='.  $book->getBuilding() .'&signature='.  $book->getSignature() .'&series='.  $book->getSeries() .'&band='.  $book->getBand() .'&avaible_copys='.  $book->getAvaibleCopys() .'"</script>';

        $DB->disconnect();
        

        
    }

    
}


?>