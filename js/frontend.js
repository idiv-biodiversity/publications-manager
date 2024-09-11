function filterPublications() {
    var input, filter, list, yearGroups, entries, i, j, title, authors, shouldShow, anyVisible, totalVisible = 0;
    input = document.getElementById('search-input');
    filter = input.value.toLowerCase();
    list = document.getElementById('publications-list');
    yearGroups = list.getElementsByClassName('year-group');
    var noPublicationsMessage = document.getElementById('no-publications-message');

    for (i = 0; i < yearGroups.length; i++) {
        entries = yearGroups[i].getElementsByClassName('publication-entry');
        anyVisible = false;

        for (j = 0; j < entries.length; j++) {
            title = entries[j].getAttribute('data-title').toLowerCase();
            authors = entries[j].getAttribute('data-authors').toLowerCase();
            shouldShow = title.includes(filter) || authors.includes(filter);
            entries[j].style.display = shouldShow ? '' : 'none';
            if (shouldShow) anyVisible = true;
        }

        yearGroups[i].style.display = anyVisible ? '' : 'none';
        if (anyVisible) totalVisible++;
    }

    // Show "No publication found." message if no results are visible
    noPublicationsMessage.style.display = totalVisible > 0 ? 'none' : 'block';
}

function filterByYear() {
    var select, year, list, yearGroups, i, shouldShow, anyVisible = false;
    select = document.getElementById('year-select');
    year = select.value;
    list = document.getElementById('publications-list');
    yearGroups = list.getElementsByClassName('year-group');
    var noPublicationsMessage = document.getElementById('no-publications-message');

    for (i = 0; i < yearGroups.length; i++) {
        shouldShow = !year || yearGroups[i].getAttribute('data-year') === year;
        yearGroups[i].style.display = shouldShow ? '' : 'none';
        if (shouldShow && yearGroups[i].getElementsByClassName('publication-entry').length > 0) {
            anyVisible = true;
        }
    }

    // Show "No publication found." message if no results are visible
    noPublicationsMessage.style.display = anyVisible ? 'none' : 'block';
}


