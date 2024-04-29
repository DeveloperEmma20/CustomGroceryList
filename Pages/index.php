<?php
    require_once('db.php');
    session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script src="//cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/yzni1ec6dq4vmf3a99ipod5po3hq970jitpi01lx0jzwshse/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script type="module" src="./json/meals.json"></script>
    <script type="module" src="./json/ingredients.json"></script>
    <script data-main="js/config.js" src="js/require-2.3.6.js"></script> <!--script data-main="js/config.js" src="js/require-2.3.6.js"></script> -->
    <script type="text/javascript" src="js/classes/Meal.js"></script>
    <script type="text/javascript" src="js/classes/Ingredient.js"></script>
    <script src="node_modules/mathjs/dist/math.min.js"></script>    
    <link rel="stylesheet" href="../Pages/css/indexPage.css" />
    <!-- Selector for Part 3 Text Area -->
    <script>
        tinymce.init({
            selector: '#cookingView',
        });
    </script>
</head>

<body>

    <?php
    // Create JSON Files For Both Meals & Ingredients
    // Meals
    $getMeals = "SELECT * FROM meals";
    $meals = mysqli_query($connect, $getMeals);

    $json_array = array();
    while($thisMeal = mysqli_fetch_assoc($meals)) {
        $json_array[] = $thisMeal;
    }
    $encodedMeals = json_encode($json_array);
    file_put_contents('json/meals.json', $encodedMeals);

    // Ingredients
    $getIngredients = "SELECT * FROM ingredients";
    $ingredients = mysqli_query($connect, $getIngredients);

    $json_array2 = array();
    while($thisIngredient = mysqli_fetch_assoc($ingredients)) {
        $json_array2[] = $thisIngredient;
    }
    $encodedIngredients = json_encode($json_array2);
    file_put_contents('json/ingredients.json', $encodedIngredients);
    ?>

    <!-- Top Buttons -->
    <!-- Add or Improve Meal Buttons -->
    <div class="mealButtons" id="mealButtons">
        <div class="button">
            <button id="addMealBtn" name="addMealBtn" class="MealBtns" onclick="addMeal()">Add Meal</button>
        </div>
        <!--<div class="button">
            <button id="improveMealBtn" name="improveMealBtn" class="MealBtns" onclick="improveMeal()">Improve Meal</button>
        </div>-->
        <div class="button">
            <button id="readyBtn" name="readyBtn" class="MealBtns" onclick="cookingView()">Cooking View</button>
        </div>
    </div>

    <div class="indexHeader">
        <h1 id="indexHeader">Welcome to Custom Grocery List!</h1>
    </div>

    <div class="row">
        <!-- Left Side -->
        <div class="column1">
            <table id="mealPlanTable">
  
            </table>
        </div>

        <!-- Right Side - Grocery List -->
        <div class="column2" id="groceryList" name="groceryList">
            <table id="groceryListTable">
                <tr class="glHeaders">
                    <th><a href="#"></a>Remove</th>
                    <th><a href="#">Quantity</a></th>
                    <th><a href="#">Ingredient</a></th>
                    <th><a href="#">Price</a></th>
                    <th><a href="#">Kroger Aisle</a></th>
                    <th><a href="#">Walmart Aisle</a></th>
                    <th><a href="#">Notes<br />About Ingredient/Aisle</a></th>
                </tr>
            </table>

            <!--<div class="groceryListTotal" id="groceryListTotal" name="groceryListTotal">
            Total Estimate: $0.00
        </div>-->

            <!-- Top Buttons -->
            <!-- Toggle Grocery List For Cooking Instructions -->
            <div class="button">
                <button id="addItemBtn" name="addItemBtn" class="addItemBtn" onclick="addItemBtn()">Add Item</button>
            </div>

        </div>

        <!-- Right Side - Cooking View -->
        <div class="column2" id="directionsArea" name="directionsArea">
            <textarea id="cookingView" name="cookingView"><center><b>Please select the meal you would like to view the directions for.</b><br /><small>Note: Editing here will <i>not</i> permanently change your meal.</small></center></textarea>
            <button id="backButton" class="backButton" onclick="homePage()">Back</button>
        </div>
            
    </div> <!-- End "row" of left and right columns -->

    <script>
        // Global variables
        var groceryListItems = 0;
        var addedGlItemsIndex = 0;
        let allMeals = [];
        let allIngredients = [];
        document.getElementById('directionsArea').hidden = true;

        addFirstItemBtn();

        // Left Side: Load meals onto screen
        // import data from './Pages/json/meals.json' and create objects from them then add to array
        fetch("./json/meals.json").then((res) => res.json()).then((meals) => {
            //console.log(meals);
            //console.log('Meal [0] = ' + meals[0].description);
            var i = 0;
            while (i < meals.length) {
                const meal = new Meal();
                meal.id = meals[i].id;
                meal.description = meals[i].description;
                meal.picture = meals[i].picture;
                meal.ingredients = meals[i].ingredients;
                meal.price = meals[i].price;
                meal.protein = meals[i].protein;
                //meal.sayYum();
                allMeals[i] = meal;
                i++;
            }
            populateMeals(allMeals);
        });

        // Add all ingredients as class objects and then to array
        fetch("./json/ingredients.json").then((res) => res.json()).then((ingredients) => {
            //console.log(ingredients);
            //console.log('Ingredient [0] = ' + ingredients[0].ingredient);
            var x = 0;
            while (x < ingredients.length) {
                const ingredient = new Ingredient();
                ingredient.id = ingredients[x].id;
                ingredient.ingredient = ingredients[x].ingredient;
                ingredient.price = ingredients[x].price;
                ingredient.kroger_aisle = ingredients[x].kroger_aisle;
                ingredient.walmart_aisle = ingredients[x].walmart_aisle;
                ingredient.aisle_description = ingredients[x].aisle_description;
                allIngredients[x] = ingredient;
                x++;
            }
        });

        // Populate Left Side Meal Table
        function populateMeals(allMeals) {
            var x = 0;
            var i = 0;
            var y = 0;

            while (i < allMeals.length) {
                while (y < 2) {
                    // Create row <tr>
                    const row = document.createElement('tr');

                    // Create first <td>
                    const newTd = document.createElement('td');
                    // First Meal Description
                    var descriptionForNewTd = document.createTextNode(allMeals[i]['description']);
                    // break
                    var newLine = document.createElement('br');
                    // First Meal Photo Button
                    var buttonForNewTd = document.createElement('button');
                    buttonForNewTd.setAttribute('Id', 'photoBtnFor_' + i);
                    buttonForNewTd.setAttribute('class', 'photoBtn');
                    buttonForNewTd.setAttribute('name', 'photoBtn');
                    buttonForNewTd.setAttribute('onClick', 'addIngredientsToGL(' + i + ')');
                    var photoForNewTd = document.createElement('img');
                    photoForNewTd.setAttribute('src', '../mealPictures/' + allMeals[i]['picture']);
                    // First Add single <tr><td> to table
                    newTd.appendChild(descriptionForNewTd);
                    newTd.appendChild(newLine);
                    buttonForNewTd.appendChild(photoForNewTd);
                    newTd.appendChild(buttonForNewTd);

                    // First Add <td> to <tr> and add <tr> to <table>
                    row.appendChild(newTd);
                    i++;
                    x++;

                    // Create Second <td>
                    const newTd2 = document.createElement('td');
                    // Second Meal Description
                    var descriptionForNewTd2 = document.createTextNode(allMeals[i]['description']);
                    // break
                    var newLine2 = document.createElement('br');
                    // Second Meal Photo Button
                    var buttonForNewTd2 = document.createElement('button');
                    buttonForNewTd2.setAttribute('Id', 'photoBtnFor_' + i);
                    buttonForNewTd2.setAttribute('class', 'photoBtn');
                    buttonForNewTd2.setAttribute('name', 'photoBtn');
                    buttonForNewTd2.setAttribute('onClick', 'addIngredientsToGL(' + i + ')');
                    var photoForNewTd2 = document.createElement('img');
                    photoForNewTd2.setAttribute('src', '../mealPictures/' + allMeals[i]['picture']);
                    // Second Add single <tr><td> to table
                    newTd2.appendChild(descriptionForNewTd2);
                    newTd2.appendChild(newLine2);
                    buttonForNewTd2.appendChild(photoForNewTd2);
                    newTd2.appendChild(buttonForNewTd2);

                    // Second Add <td> to <tr> and add <tr> to <table>
                    row.appendChild(newTd2);
                    i++;
                    x++;

                    document.getElementById('mealPlanTable').appendChild(row);
                    y++;
                }
                y = 0;
            }
        }

        function addFirstItemBtn() {
            addedGlItemsIndex++;
            const newRow = document.createElement('tr');
            newRow.setAttribute('Id', addedGlItemsIndex.toString());

            // Add a href 'x' button"
            // createA.setAttribute('preventDefault()'); stop 'a href #' from going to top of screen?
            // Also needs function to keep user scrolled to the bottom of list after 17 items entered
            const column1 = document.createElement("td");
            column1.setAttribute('Id', 'xBtnFor_' + addedGlItemsIndex.toString());
            var createA = document.createElement('a');
            var createAText = document.createTextNode('X');
            createA.setAttribute('href', '#');
            createA.addEventListener('click', removeGroceryListItem);
            createA.appendChild(createAText);
            column1.appendChild(createA);

            // Amount
            const column2 = document.createElement("td");
            column2.setAttribute('Id', 'column2First');
            column2.setAttribute('contentEditable', true);
            var c2Text = document.createTextNode('1');
            column2.appendChild(c2Text);

            // Ingredient
            const column3 = document.createElement("td");
            column3.setAttribute('Id', 'column3First');
            column3.setAttribute('contentEditable', true);

            // Total Cost
            const column4 = document.createElement("td");
            column4.setAttribute('Id', 'column4First');
            column4.setAttribute('contentEditable', true);

            // Kroger Aisle
            const column5 = document.createElement("td");
            column5.setAttribute('Id', 'column5First');
            column5.setAttribute('contentEditable', true);

            // Walmart Aisle
            const column6 = document.createElement("td");
            column6.setAttribute('Id', 'column6First');
            column6.setAttribute('contentEditable', true);

            // Notes/Aisle Description
            const column11 = document.createElement("td");
            column11.setAttribute('Id', 'column11First');
            column11.setAttribute('contentEditable', true);

            // Append the nodes of the row:
            newRow.appendChild(column1);
            newRow.appendChild(column2);
            newRow.appendChild(column3);
            newRow.appendChild(column4);
            newRow.appendChild(column5);
            newRow.appendChild(column6);
            newRow.appendChild(column11);
            // Append the entire row to the grocery list
            document.getElementById("groceryListTable").appendChild(newRow);
            // Add to amount of items
            groceryListItems++;
        }

        function addItemBtn() {
            addedGlItemsIndex++;
            const newRow = document.createElement('tr');
            newRow.setAttribute('Id', addedGlItemsIndex.toString());

            // Add a href 'x' button"
            // createA.setAttribute('preventDefault()'); stop 'a href #' from going to top of screen?
            // Also needs function to keep user scrolled to the bottom of list after 17 items entered
            const column1 = document.createElement("td");
            column1.setAttribute('Id', 'xBtnFor_' + addedGlItemsIndex.toString());
            var createA = document.createElement('a');
            var createAText = document.createTextNode('X');
            createA.setAttribute('href', '#');
            createA.addEventListener('click', removeGroceryListItem);
            createA.appendChild(createAText);
            column1.appendChild(createA);

            // Amount
            const column2 = document.createElement("td");
            column2.setAttribute('Id', 'column2');
            column2.setAttribute('contentEditable', true);
            var c2Text = document.createTextNode('1');
            column2.appendChild(c2Text);

            // Ingredient
            const column3 = document.createElement("td");
            column3.setAttribute('Id', 'column3');
            column3.setAttribute('contentEditable', true);

            // Total Cost
            const column4 = document.createElement("td");
            column4.setAttribute('Id', 'column4');
            column4.setAttribute('contentEditable', true);

            // Kroger Aisle
            const column5 = document.createElement("td");
            column5.setAttribute('Id', 'column5');
            column5.setAttribute('contentEditable', true);

            // Walmart Aisle
            const column6 = document.createElement("td");
            column6.setAttribute('Id', 'column6');
            column6.setAttribute('contentEditable', true);

            // Notes/Aisle Description
            const column11 = document.createElement("td");
            column11.setAttribute('Id', 'column11');
            column11.setAttribute('contentEditable', true);

            // Append the nodes of the row:
            newRow.appendChild(column1);
            newRow.appendChild(column2);
            newRow.appendChild(column3);
            newRow.appendChild(column4);
            newRow.appendChild(column5);
            newRow.appendChild(column6);
            newRow.appendChild(column11);
            // Append the entire row to the grocery list
            document.getElementById("groceryListTable").appendChild(newRow);
            // Add to amount of items
            groceryListItems++;
        }

        function addMealIngredients(ingredient, price, kroger_aisle, walmart_aisle, aisle_description) {
            // Check if the display row is empty, delete it if so
            if (addedGlItemsIndex == 1 && (document.getElementById('column3First') != null) // not deleted already
                                        && (document.getElementById('column3First').innerHTML.localeCompare('') == 0)
                                        && (document.getElementById('column4First').innerHTML.localeCompare('') == 0)
                                        && (document.getElementById('column5First').innerHTML.localeCompare('') == 0)
                                        && (document.getElementById('column6First').innerHTML.localeCompare('') == 0)
                                        && (document.getElementById('column11First').innerHTML.localeCompare('') == 0)) {
                document.getElementById("groceryListTable").deleteRow(1);
            } else {
                addedGlItemsIndex++;
            }

            const newRow = document.createElement('tr');
            newRow.setAttribute('Id', addedGlItemsIndex.toString());

            // Add a href 'x' button"
            // createA.setAttribute('preventDefault()'); stop 'a href #' from going to top of screen?
            // Also needs function to keep user scrolled to the bottom of list after 17 items entered
            const column1 = document.createElement("td");
            column1.setAttribute('Id', 'xBtnFor_' + addedGlItemsIndex.toString());
            var createA = document.createElement('a');
            var createAText = document.createTextNode('X');
            createA.setAttribute('href', '#');
            createA.addEventListener('click', removeGroceryListItem);
            createA.appendChild(createAText);
            column1.appendChild(createA);

            // Amount
            const column2 = document.createElement("td");
            column2.setAttribute('Id', 'column2');
            column2.setAttribute('contentEditable', true);
            var c2Text = document.createTextNode('1');
            column2.appendChild(c2Text);

            // Ingredient
            const column3 = document.createElement("td");
            column3.setAttribute('Id', 'column3');
            column3.setAttribute('contentEditable', true);
            var ingredientName = document.createTextNode(ingredient);
            column3.appendChild(ingredientName);

            // Price
            const column4 = document.createElement("td");
            column4.setAttribute('Id', 'column4');
            column4.setAttribute('contentEditable', true);
            var ingredientPrice = document.createTextNode(price);
            column4.appendChild(ingredientPrice);

            // Kroger Aisle
            const column5 = document.createElement("td");
            column5.setAttribute('Id', 'column5');
            column5.setAttribute('contentEditable', true);
            var kAisle = document.createTextNode(kroger_aisle);
            column5.appendChild(kAisle);

            // Walmart Aisle
            const column6 = document.createElement("td");
            column6.setAttribute('Id', 'column6');
            column6.setAttribute('contentEditable', true);
            var wAisle = document.createTextNode(walmart_aisle);
            column6.appendChild(wAisle);

            // Notes/Aisle Description
            const column11 = document.createElement("td");
            column11.setAttribute('Id', 'column11');
            column11.setAttribute('contentEditable', true);
            var description = document.createTextNode(aisle_description);
            column11.appendChild(description);

            // Append the nodes of the row:
            newRow.appendChild(column1);
            newRow.appendChild(column2);
            newRow.appendChild(column3);
            newRow.appendChild(column4);
            newRow.appendChild(column5);
            newRow.appendChild(column6);
            newRow.appendChild(column11);
            // Append the entire row to the grocery list
            document.getElementById("groceryListTable").appendChild(newRow);
            // Add to amount of items
            groceryListItems++;
        }

        function removeGroceryListItem() {
            const childElement = this;
            const parentElement = childElement.parentElement;
            var index = 0;

            index = parseInt(parentElement.getAttribute('Id').substring(8));
            // Delete the row
            document.getElementById("groceryListTable").deleteRow(index);
            // lower the index after deleting columns
            addedGlItemsIndex--;

            // Rename remaining rows
            var numRowsAfterDeleted = document.getElementById("groceryListTable").rows.length - index;
            // Rename the remaining rows
            var nextColumn = index + 1;
            while (numRowsAfterDeleted > 0) {
                const nextTdElementToDecrease = document.getElementById('xBtnFor_' + nextColumn.toString());
                var oldIdNum = parseInt(nextTdElementToDecrease.getAttribute('id').substring(8));
                var newIdNum = oldIdNum - 1;
                var newId = 'xBtnFor_' + newIdNum;
                nextTdElementToDecrease.removeAttribute('id'); // Refresh
                nextTdElementToDecrease.setAttribute('id', newId); 
                numRowsAfterDeleted--;
                nextColumn++;
            }
            
        }

        function removeIngredientBrackets(ingredientsArrayStr) {
            if (ingredientsArrayStr.includes("[")) {
                ingredientsArrayStr = ingredientsArrayStr.replaceAll("[", "")
            }
            if (ingredientsArrayStr.includes("]")) {
                ingredientsArrayStr = ingredientsArrayStr.replaceAll("]", "")
            }
            if (ingredientsArrayStr.includes("{")) {
                ingredientsArrayStr = ingredientsArrayStr.replaceAll("{", "")
            }
            if (ingredientsArrayStr.includes("}")) {
                ingredientsArrayStr = ingredientsArrayStr.replaceAll("}", "")
            }
            return ingredientsArrayStr;
        }

        function displayCookingView(i) {
            // Clear editor and display cooking directions
            tinyMCE.activeEditor.setContent('');
            const fileName = '../mealDirections/' + allMeals[i].description.replaceAll(' ', '');

            fetch(fileName + ".txt").then((res) => res.text()).then((text) => {
                tinymce.activeEditor.execCommand('mceReplaceContent', true, text + '<br><br>');
            }).catch((e) => console.error(e));
        }

        function addIngredientsToGL(i) {
            // allMeals[2]['ingredients'] prints out [{14},{15}]
            //allIngredients[x]['id']
            var mealIngredientIds = [];
            var mealIngredientIds = removeIngredientBrackets(allMeals[i]['ingredients']).split('_'); // [{14}_{15}]
            var x = 0;
            var ingredientId = '';
            var price = '';
            var ingredient = '';
            var kroger_aisle = '';
            var aisle_description = '';

            while (x < mealIngredientIds.length) {
                //console.log(mealIngredientIds[x]);
                ingredientId = mealIngredientIds[x];
                var y = 0;
                while (y < allIngredients.length) {
                    //console.log("here");
                    //console.log(allIngredients[y]['id']);
                    //console.log(ingredientId);
                    if (parseInt(allIngredients[y]['id']) == parseInt(ingredientId)) {
                        // Clear empty first row if still empty
                        //var zero = 0;
                        //if (document.getElementById('xBtnFor_' + zero.toString()) {
                        //    // What the heck to do do something
                        //}
                        // Get data
                        price = allIngredients[y]['price'];
                        ingredient = allIngredients[y]['ingredient'];
                        kroger_aisle = allIngredients[y]['kroger_aisle'];
                        walmart_aisle = allIngredients[y]['walmart_aisle'];
                        aisle_description = allIngredients[y]['aisle_description'];

                        //// Update the total
                        //var totalStr = document.getElementById('groceryListTotal');
                        //// Total was 0.00 before, just update total
                        //if (totalStr.textContent == 'Total Estimate: $0.00 ') {
                        //    totalStr.textContent = 'Total Estimate: $' + allMeals[i]['price'].substring(1);
                        //}

                        // Add it to the grocery list
                        addMealIngredients(ingredient, price, kroger_aisle, walmart_aisle, aisle_description);
                    }
                    y++;
                }

                x++;
            }
        }

        function cookingView() {
            window.open('cookingPage.php', '_self');
        }

        //function improveMeal() {
        //    window.open('improveMealPage.php','_self');
        //}

        function addMeal() {
            window.open('addMealPage.php','_self');
        }

    </script>



</body>
</html>
