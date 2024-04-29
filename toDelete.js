while (x < 3) {
    if (x == 0 & y == 0) {
        // first row
        // add <tr>
        const row = document.createElement('tr');
    }
    // while ($meal = mysqli_fetch_assoc($meals)) {
    while (i < allMeals.length) {
        bID++;

        // add <td> with meal description
        const tableData = document.createElement('td');
        var description = document.createTextNode(allMeals[i]['description']);
        tableData.appendChild(description);

        // add <br>
        linebreak = document.createElement("br");
        tableData.appendChild(linebreak);

        // add meal photo button id 'photoBtn' class 'photoBtn'
        var buttonForNewTd = document.createElement('button');
        buttonForNewTd.setAttribute('Id', 'photoBtn');
        buttonForNewTd.setAttribute('class', 'photoBtn');
        buttonForNewTd.setAttribute('name', 'photoBtn');
        var photoForNewTd = document.createElement('img');
        photoForNewTd.setAttribute('src', '../mealPictures/ChiliAndTacos.jpg');

        // Add new <td> to <tr>
        tableData.appendChild(descriptionForNewTd);
        tableData.appendChild(newLine);
        buttonForNewTd3.appendChild(photoForNewTd);
        tableData.appendChild(buttonForNewTd);
        newRow.appendChild(tableData);

        newRow.appendChild(tableData);
        document.getElementById('mealPlanTable').appendChild(newRow);

        y++;
        i++;

        // check to see if y == 3  need to <br> to new row of meals
        if (y == 3) {
            // </tr><tr> and reset y = 0
            tbl.appendChild(row);
            row = document.createElement('tr');
            y = 0;
        }
    }
    // x++
    x++;
}
// Dont forget to add it all together/ children to parent div
// div, tbl,
document.getElementById("mealPlanTable").appendChild(tbl);