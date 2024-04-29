<?php
    require_once('db.php');
    session_start();
    error_reporting( E_ALL );

    // Executes after finish button is clicked and JavaScript uses AJAX to post this data back to this same page
    if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['mealName'], $_POST['photo'], $_POST['directions'], $_POST['protein'], $_POST['mealOrDish'], $_POST['occasion'], $_POST['type'], $_POST['ingredients'])) {
        ob_clean();

        // Upload photo
        $file_name_new = 'none';
        if(isset($_FILES['photo'])) {
            $file_name_new = 'something';
            $file = $_FILES['photo'];
            // File Properties
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            // Work out the file extension
            $file_ext = explode('.', $file_name);
            $file_ext = strtolower(end($file_ext));
            // Rename file
            $file_name_new = str_replace($_POST['mealName'], ' ', '') . '.' . $file_ext;
            $file_destination = '../../mealPictures/' . $file_name_new;
            // Upload file to application
            move_uploaded_file($file_tmp, $file_destination);
        }

        // Add ingredients to ingredients table
        $ingredientsToAdd = addIngredients($_POST['ingredients']);

        // Add meals to meals table
        $mealName = $_POST['mealName'];
        $photo = 'cantUpload.jpg';
        $directions = $_POST['directions']; // Needs updated
        $directionsLength = strval(strlen($directions));
        $ingredients = $_POST['ingredients']; // will be updated below after time has passed for ingredients to add to database
        $price = "$0.00"; // Needs updated
        $protein = $_POST['protein'];
        $mealOrDish = $_POST['mealOrDish'];
        $occasion = $_POST['occasion'];
        $type = $_POST['type'];

        // Saved directions to a file rather than to the database because such large data
        // Named the file the mealName with no spaces
        $fileName = str_replace(' ', '', $mealName);
        $newFile = fopen("../mealDirections/" . $fileName . ".txt", "w");
        fwrite($newFile, $directions);
        fclose($newFile);

        $price = getMealPrice($_POST['ingredients']);

        // Get the ingredient IDs from table now they are all added
        $ingredients = getIngredientsForMeals($ingredientsToAdd); // generates the [{#},{}] string

        // Save everything to database (only holds up to 30 meals)
        $connect = $mysqli = new mysqli("localhost", "root", "", "grocerylist");
        $getMeals = "SELECT * FROM meals";
        $meals = mysqli_query($connect, $getMeals);
        $i = 0;
        while($thisMeal = mysqli_fetch_assoc($meals)) {
            $i++;
        }
        if ($i < 30) {
            $insertMealStmt = "INSERT INTO meals (description, picture, ingredients, price, protein, mealOrDish, occasion, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";      
            $insertMealStmt = $connect->prepare($insertMealStmt);
            $insertMealStmt->bind_param("ssssssss", $mealName, $photo, $ingredients, $price, $protein, $mealOrDish, $occasion, $type);
            $insertMealStmt->execute();
        } else {
            echo("This site is only allowing 30 meals to be added at this time and the limit is reached.");
        }

        die();

    }

    function getIngredientsForMeals($ingredientsToAdd) {
        $ingredientsForMeals = "["; // [{34},{10}]

        // REDO
        for ($x = 0; $x <= count($ingredientsToAdd); $x++) {
            $connect = $mysqli = new mysqli("localhost", "root", "", "grocerylist");
            $getIngredients = "SELECT id, ingredient FROM ingredients";
            $ingredients = mysqli_query($connect, $getIngredients);
            while($ingredient = mysqli_fetch_assoc($ingredients)) {
                if (strtoupper($ingredient["ingredient"]) == strtoupper($ingredientsToAdd[$x])) {
                    //echo("match!:" . $ingredientsToAdd[$x] . " matches " . $ingredient["ingredient"]);
                    // Found in table, add ID to string
                    $str = "{" . strval($ingredient["id"]) . "}_";
                    $ingredientsForMeals .= $str;
                }
            }
        }




        return substr($ingredientsForMeals, 0, strlen($ingredientsForMeals) - 1) . "]";
    }

    function addIngredients($newIngredients) {
        $newIngredientsCopy = $newIngredients;
        //var_dump($newIngredients);
        // Dumps: string(259) "{"ingredient":"Ketchup","price":"$2.50","notes":"k","kAisle":"k","wAisle":"k"}{"ingredient":"corn","price":"n","notes":"n","kAisle":"n","wAisle":"n"}" 
        // Store all of the new ingredients into an array
        $newIngrediends = substr($newIngredients, 13); // cuts off string(x) beginning of $newIngredients
        $ingredientsToAdd = [];
        $price = [];
        $kroger_aisle = [];
        $walmart_aisle = [];
        $notes = [];
        $x = 0;

        while (str_contains($newIngredients, "ingredient")) {
            $pos1 = strpos($newIngredients, ":") + 2;
            $pos2 = strpos($newIngredients, ",") - 1;
            $length = $pos2 - $pos1;
            $ingredientsToAdd[$x] = substr($newIngredients, $pos1, $length);
            // Cut off last ingredient to get to next ingredient
            $newIngredients = substr($newIngredients, 15);
            if (str_contains($newIngredients, "ingredient")) {
                $pos3 = strpos($newIngredients, "ingredient");
                $newIngredients = substr($newIngredients, $pos3);
            }
            $x++;
        }
        $newIngredients = $newIngredientsCopy;
        $x = 0;
        $position = 0;
        while (str_contains($newIngredients, "price")) {
            $position = strpos($newIngredients, "price");
            $newIngredients = substr($newIngredients, $position);
            $pos1 = strpos($newIngredients, ":") + 2;
            $pos2 = strpos($newIngredients, ",") - 1;
            $length = $pos2 - $pos1;
            $price[$x] = substr($newIngredients, $pos1, $length);
            // Cut off last ingredient to get to next ingredient
            $newIngredients = substr($newIngredients, 15);
            if (str_contains($newIngredients, "price")) {
                $pos3 = strpos($newIngredients, "price");
                $newIngredients = substr($newIngredients, $pos3);
            }
            $x++;
        }
        $newIngredients = $newIngredientsCopy;
        $x = 0;
        $position = 0;
        while (str_contains($newIngredients, "kAisle")) {
            $position = strpos($newIngredients, "kAisle");
            $newIngredients = substr($newIngredients, $position);
            $pos1 = strpos($newIngredients, ":") + 2;
            $pos2 = strpos($newIngredients, ",") - 1;
            $length = $pos2 - $pos1;
            $kroger_aisle[$x] = substr($newIngredients, $pos1, $length);
            // Cut off last ingredient to get to next ingredient
            $newIngredients = substr($newIngredients, 15);
            if (str_contains($newIngredients, "kAisle")) {
                $pos3 = strpos($newIngredients, "kAisle");
                $newIngredients = substr($newIngredients, $pos3);
            }
            $x++;
        }
        $newIngredients = $newIngredientsCopy;
        $x = 0;
        $position = 0;
        while (str_contains($newIngredients, "wAisle")) {
            $position = strpos($newIngredients, "wAisle");
            $newIngredients = substr($newIngredients, $position);
            $pos1 = strpos($newIngredients, ":") + 2;
            $pos2 = strpos($newIngredients, "}") - 1;
            $length = $pos2 - $pos1;
            $walmart_aisle[$x] = substr($newIngredients, $pos1, $length);
            // Cut off last ingredient to get to next ingredient
            $newIngredients = substr($newIngredients, 15);
            if (str_contains($newIngredients, "wAisle")) {
                $pos3 = strpos($newIngredients, "wAisle");
                $newIngredients = substr($newIngredients, $pos3);
            }
            $x++;
        }
        $newIngredients = $newIngredientsCopy;
        $x = 0;
        $position = 0;
        while (str_contains($newIngredients, "notes")) {
            $position = strpos($newIngredients, "notes");
            $newIngredients = substr($newIngredients, $position);
            $pos1 = strpos($newIngredients, ":") + 2;
            $pos2 = strpos($newIngredients, ",") - 1;
            $length = $pos2 - $pos1;
            $notes[$x] = substr($newIngredients, $pos1, $length);
            // Cut off last ingredient to get to next ingredient
            $newIngredients = substr($newIngredients, 15);
            if (str_contains($newIngredients, "notes")) {
                $pos3 = strpos($newIngredients, "notes");
                $newIngredients = substr($newIngredients, $pos3);
            }
            $x++;
        }

        // Check to see if ingredient isn't already in the table, add if not
        $connect = $mysqli = new mysqli("localhost", "root", "", "grocerylist");
        $getIngredients = "SELECT id, ingredient FROM ingredients";
        $ingredients = mysqli_query($connect, $getIngredients);
        $matchFound = false;
        $x = 0;
        foreach ($ingredientsToAdd as $i) {
            while($ingredient = mysqli_fetch_assoc($ingredients)) {
                if (strtoupper($ingredient["ingredient"]) == strtoupper($i)) {
                    $matchFound = true;
                }
            }
            if ($matchFound == false) {
                // Add Ingredient To Ingredients Table
                $connect = $mysqli = new mysqli("localhost", "root", "", "grocerylist");
                $insertIngStmt = "INSERT INTO ingredients (ingredient, price, kroger_aisle, walmart_aisle, aisle_description) VALUES (?, ?, ?, ?, ?)";      
                $insertIngStmt = $connect->prepare($insertIngStmt);
                $insertIngStmt->bind_param("sssss", $ingredientsToAdd[$x], $price[$x], $kroger_aisle[$x], $walmart_aisle[$x], $notes[$x]);
                $insertIngStmt->execute();
            }
            $x++;
        }
        return ($ingredientsToAdd);
    }

    function getMealPrice($newIngredients) {
        $price = 0;
        $newIngredients;
        //var_dump($newIngredients);
        // Dumps: string(259) "{"ingredient":"Ketchup","price":"$2.50","notes":"k","kAisle":"k","wAisle":"k"}{"ingredient":"corn","price":"n","notes":"n","kAisle":"n","wAisle":"n"}" 
        // Store all of the new ingredients into an array
        $newIngrediends = substr($newIngredients, 13); // cuts off string(x) beginning of $newIngredients
        $prices = [];
        $x = 0;
        $position = 0;
        while (str_contains($newIngredients, "price")) {
            $position = strpos($newIngredients, "price");
            $newIngredients = substr($newIngredients, $position);
            $pos1 = strpos($newIngredients, ":") + 2;
            $pos2 = strpos($newIngredients, "_") - 1;
            $length = $pos2 - $pos1;
            $prices[$x] = substr($newIngredients, $pos1, $length);
            // Cut off last ingredient to get to next ingredient
            $newIngredients = substr($newIngredients, 15);
            if (str_contains($newIngredients, "price")) {
                $pos3 = strpos($newIngredients, "price");
                $newIngredients = substr($newIngredients, $pos3);
            }
            $x++;
        }

        // parse through $prices[] and add all values
        foreach ($prices as $p) {
            if (str_contains($p, "$")) {
                $position = strpos($p, "$");
                $p = substr($p, 1);
            }
            $floatPrice = floatval($p);
            $price += $floatPrice;
        }

        if (!str_contains(strval($price), ".")) {
            return "$" . strval($price) . ".00";
        } else {
            return "$" . strval($price);
        }
    }

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="css/addMealPage.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/yzni1ec6dq4vmf3a99ipod5po3hq970jitpi01lx0jzwshse/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script data-main="js/config.js" src="js/require-2.3.6.js"></script> <!--script data-main="js/config.js" src="js/require-2.3.6.js"></script>-->
    <script type="text/javascript" src="js/classes/Ingredient.js"></script>


    <!-- Selector for Part 3 Text Area -->
    <script>
            tinymce.init({
                selector: '#directions'
            });
    </script>
</head>

<body>

    <!-- Top Buttons -->
    <div class="mealButtons">
        <button id="backBtn" class="backBtn" onclick="homePage()">Go Back</button>
    </div>

    <div class="formSection" id="formSection" enctype="multipart/form-data">
        <h1 class="header">Add a New Meal</h1>
        <form action="index.php" method="post" id="AddEditMealForm" class="AddEditMealForm" alt="Add/Edit A Meal" enctype="multipart/form-data">

            <!-- Part 1: Meal Name -->
            <!-- Go to PetPlaymates to see how 'if isset(variable)...':     value=" <php if (isset($_POST['submit'])) { echo $userName; } ?> " -->
            <h2>
               1) Easy Things First, Describe Your Meal Using A Short Meal Description:
            </h2>
            <input type="text" class="mealName" id="mealName" name="mealName" maxlength="43" placeholder="Meatloaf with Roasted Red Potatoes" required />



            <!-- Part 2: Upload Photo -->
            <h2>
                2) Upload A Photo Of The Meal - (450px x 270px) (WxH) For Best Display:
            </h2>
            <input type="file" id="photo" name="photo" accept="image/png, image/jpg, image/avif, image/jpeg" required />

            <!-- Part 3: Additional Questions About Meal -->
            <h2>
                3) A Handful Of Questions About Your Meal:
            </h2>
            <label class="proteinLabel">
                1. What Is The Main Protein In This Meal?
                <select name="protein" id="protein" class="question" required>
                    <option value="Beef">Beef</option>
                    <option value="Chicken">Chicken</option>
                    <option value="Eggs">Eggs</option>
                    <option value="Fish">Fish</option>
                    <option value="Lamb">Lamb</option>
                    <option value="None">None</option>
                    <option value="Pork">Pork</option>
                    <option value="Shrimp">Shrimp</option>
                    <option value="Tempeh">Tempeh</option>
                    <option value="Tofu">Tofu</option>
                    <option value="Turkey">Turkey</option>
                    <option value="Vegetarian">Vegetarian</option>
                    <option value="Variety">Variety</option>
                </select>
            </label>
            <label class="dishLabel">
                2. Can This Meal Be Considered A Single Dish?
                <select name="mealOrDish" id="mealOrDish" class="question" required>
                    <option value="No">No</option>
                    <option value="Yes">Yes</option>
                </select>
            </label>
            <label class="occasionLabel">
                3. What Occasion Is Associated With This Meal?
                <select name="occasion" id="occasion" class="question" required>
                    <option value="Any Day">Any Day</option>
                    <option value="4th Of July">4th of July</option>
                    <option value="Birthday">Birthday</option>
                    <option value="Christmas">Christmas</option>
                    <option value="Easter">Easter</option>
                    <option value="Easter">Halloween</option>
                    <option value="New Year's Eve">New Year's Eve</option>
                    <option value="St. Patrick's Day">St. Patrick's Day</option>
                    <option value="Thanksgiving">Thanksgiving</option>
                    <option value="Valentine's Day">Valentine's Day</option>
                </select>
            </label>
            <label class="typeLabel">
                4. What Type Of Meal Is This?
                <select name="type" id="type" class="question" required>
                    <option value="Breakfast">Breakfast</option>
                    <option value="BreakfastSide">Breakfast Side Dish</option>
                    <option value="Brunch">Brunch</option>
                    <option value="Lunch">Lunch</option>
                    <option value="Dinner">Dinner</option>
                    <option value="DinnerSide">Dinner Side Dish</option>
                    <option value="Snack">Snack</option>
                    <option value="Dessert">Dessert</option>

                </select>
            </label>

            <!-- Part 4: Ingredient List/Directions using WYSIWYG HTML Textarea Editor -->
            <h2>
                4) Write Out The Ingredients At The Top & Cooking Directions Below:
            </h2>
            <textarea id="directions" name="directions"></textarea>

            <!-- Part 5: Ingredient Details -->
            <div>
                <h2>
                    5) For Shopping Purposes, Provide Details About Each Ingredient For The Meal:
                </h2>
            </div>

            <div id="ingredientsList" class="ingredientsList">
                <div class="lastIngredientAdded" id="lastIngredientAdded">
                    First Ingredient (required)
                </div>
                <div class="row1">
                    <div class="column1">
                        <label name="ingredientLabel" class="needsIndented">
                            Ingredient:
                            <input id="ingredient" type="text" class="ingredient" name="ingredient" placeholder="Ketchup" value="" required />
                        </label>
                        <label name="ingredientLabel">
                            Price Estimate:
                            <input id="price" type="text" class="price" name="price" placeholder="" value="$2.50" required />
                        </label>
                        <br />
                        <label name="ingredientLabel" class="needsIndented">
                            Kroger Aisle:
                            <input id="kAisle" type="text" class="kAisle" name="kAisle" placeholder="10" value="" required />
                        </label>
                        <label name="ingredientLabel">
                            Walmart Aisle:
                            <input id="wAisle" type="text" class="wAisle" name="wAisle" placeholder="4" value="" required />
                        </label>
                        <br />
                        <label name="ingredientLabel" class="needsIndented">
                            Notes:
                            <input id="notes" type="text" class="aisleDescription" name="aisleDescription" placeholder="31oz Heinz in Condiments Aisle" value="" required />
                        </label>
                    </div>

                    <!-- Remove button - no functionality - To add when you want to display all of the ingredients dynamically naming each field
                                                            Will need to rename fields to ingredient + ingredientCounter
                                                            and will need to change "refresh ingredients" to add the input fields in javascript
                                                            and will need to change code to add all to ingredientsJSON at the end to make sure
                                                            all of the fields are updated at the end if a user goes back to edit an earlier ingredient
                                                            rather than changing them throughout the process.
                    <div class="column2">
                        <button class="xBtnIngredient" id="xBtnFor_0">Remove</button>
                    </div>-->
                </div>
            </div>

            <!-- Go To Next Ingredient Button -->
            <div class="nextIngredientBtnArea">
                <button id="nextIngredientBtn" class="nextIngredientBtn" onclick="addIngredient()">Add Another Ingredient</button>
            </div>

            <!-- Submit Button -->
            <button name="finishBtn" id="finishBtn" class="finishBtn" onclick="finishBtnClick()">Finish</button>
        </form>
    </div>

    <script>
        var newIngredientCounter = 0;
        var colorCounter = 1;
        let newIngredientJSON = '';

        function grabNextColor() {
            colorCounter++;
            if (colorCounter == 1) return 'pink';
            if (colorCounter == 2) return 'lightgoldenrodyellow';
            if (colorCounter == 3) return '#E0B0FF';
            if (colorCounter == 4) return 'lightgreen';
            if (colorCounter == 5) return 'lightcoral';
            if (colorCounter == 6) return '#8b9dc3';
            if (colorCounter == 7) return 'mediumaquamarine';
            if (colorCounter == 8) return 'peachpuff';
            if (colorCounter == 9) return 'thistle';
            if (colorCounter == 10) return 'lightsalmon';
            if (colorCounter == 11) return '#d2e7ff';
            if (colorCounter == 12) return '#ffa500';
            if (colorCounter == 13) return '#f1cbff';
            if (colorCounter == 14) return '#FFFF8F';
            // loop back through
            if (colorCounter == 15) colorCounter = 0;
        }

        function resetIngredients(lastIngredientName) {
            // Reset background color and display last ingredient just added
            const outerDiv = document.getElementById('ingredientsList');
            outerDiv.setAttribute('style', 'background-color: ' + grabNextColor() + ';');
            const lastIngredientAdded = document.getElementById('lastIngredientAdded');
            lastIngredientAdded.innerHTML = 'Last ingredient added: ' + lastIngredientName;

            // Reset the ingredients sections 5 on screen
            document.getElementById('ingredient').value = '';
            document.getElementById('price').value = '';
            document.getElementById('notes').value = '';
            document.getElementById('kAisle').value = '';
            document.getElementById('wAisle').value = '';
        }

        // Continue to add ingredents to a meal
        function addIngredient() {
            newIngredientCounter++;

            // Make object of ingredient added
            let newIngredient = new Ingredient();
            newIngredient.id = newIngredientCounter.toString();
            newIngredient.ingredient = document.getElementById('ingredient');
            newIngredient.price = document.getElementById('price');
            newIngredient.aisle_description = document.getElementById('notes');
            newIngredient.kroger_aisle = document.getElementById('kAisle');
            newIngredient.walmart_aisle = document.getElementById('wAisle');

            // Prepare it for JSON.stringify()
            let newIngredientPrepared = {
                id: newIngredient.id.value,
                ingredient: newIngredient.ingredient.value,
                price: newIngredient.price.value,
                notes: newIngredient.aisle_description.value,
                kAisle: newIngredient.kroger_aisle.value,
                wAisle: newIngredient.walmart_aisle.value,
            };

            // Add ingredient to newIngredientJSON to eventually return to PHP as a cookie
            newIngredientJSON += JSON.stringify(newIngredientPrepared);

            // Prepare for next ingredient entry
            resetIngredients(newIngredient.ingredient.value);

        }

        async function finishBtnClick() {
            // Refresh/grab the last ingredient added
            addIngredient();

            // Grab the image


            // AJAX calls external PHP file which adds everything to tables
            var mealName = $('#mealName').val();
            var photo = $('#photo').val();
            var directions = tinymce.get('directions').getContent();
            var protein = $('#protein').val();
            var mealOrDish = $('#mealOrDish').val();
            var occasion = $('#occasion').val();
            var type = $('#type').val();

            $.ajax({
                type:'POST',
                url: location.href,
                data: ({
                    'mealName': mealName,
                    'photo': photo,
                    'directions': directions,
                    'protein': protein,
                    'mealOrDish': mealOrDish,
                    'occasion': occasion,
                    'type': type,
                    'ingredients': newIngredientJSON
                }),
                success:function(r) {
                    document.querySelector('div').innerHTML=r;
                }
            });

            // Sleep 60 seconds to let PHP add to database before redirect
            alert("Your meal is succesfully added!");
            setTimeout(100000);
            window.open('addMealPage.php', '_self');

        }

        function homePage() {
            window.open('index.php','_self');
        }


    </script>

</body>
</html>
