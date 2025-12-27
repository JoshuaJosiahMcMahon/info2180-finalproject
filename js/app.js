document.addEventListener('DOMContentLoaded', function() {
    checkSession();
    setupLoginForm();
    setupLogout();
    setupNavigation();
});

function checkSession() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'api/check_session.php', true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var response = xhr.responseText;
            if (response.indexOf('success') !== -1) {
                var temp = document.createElement('div');
                temp.innerHTML = response;
                var successDiv = temp.querySelector('.success');
                if (successDiv) {
                    var userData = {
                        id: successDiv.getAttribute('data-user-id'),
                        firstname: successDiv.getAttribute('data-firstname'),
                        lastname: successDiv.getAttribute('data-lastname'),
                        role: successDiv.getAttribute('data-role')
                    };
                    showMainApp(userData);
                }
            }
        }
    };
    xhr.send();
}

function setupLoginForm() {
    var loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var email = document.getElementById('login-email').value;
            var password = document.getElementById('login-password').value;
            var errorDiv = document.getElementById('login-error');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/login.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = xhr.responseText;
                    if (response.indexOf('success') !== -1) {
                        var temp = document.createElement('div');
                        temp.innerHTML = response;
                        var successDiv = temp.querySelector('.success');
                        if (successDiv) {
                            var userData = {
                                id: successDiv.getAttribute('data-user-id'),
                                firstname: successDiv.getAttribute('data-firstname'),
                                lastname: successDiv.getAttribute('data-lastname'),
                                role: successDiv.getAttribute('data-role')
                            };
                            showMainApp(userData);
                        }
                    } else {
                        errorDiv.innerHTML = response;
                    }
                }
            };
            xhr.send('email=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password));
        });
    }
}

function setupLogout() {
    var logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'api/logout.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    showLoginScreen();
                }
            };
            xhr.send();
        });
    }
}
function setupNavigation() {
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-page]') || e.target.closest('[data-page]')) {
            e.preventDefault();
            var target = e.target.matches('[data-page]') ? e.target : e.target.closest('[data-page]');
            var page = target.getAttribute('data-page');
            loadPage(page);
            document.querySelectorAll('.nav-link').forEach(function(link) {
                link.classList.remove('active');
            });
            if (target.classList.contains('nav-link')) {
                target.classList.add('active');
            }
        }

        if (e.target.matches('.contact-link') || e.target.matches('.view-contact-btn')) {
            e.preventDefault();
            var contactId = e.target.getAttribute('data-contact-id');
            viewContact(contactId);
        }

        if (e.target.matches('.filter-btn')) {
            e.preventDefault();
            var filter = e.target.getAttribute('data-filter');
            loadContacts(filter);

            document.querySelectorAll('.filter-btn').forEach(function(btn) {
                btn.classList.remove('active');
            });
            e.target.classList.add('active');
        }

        if (e.target.matches('.assign-to-me-btn')) {
            e.preventDefault();
            var contactId = e.target.getAttribute('data-contact-id');
            assignToMe(contactId);
        }

        if (e.target.matches('.toggle-type-btn')) {
            e.preventDefault();
            var contactId = e.target.getAttribute('data-contact-id');
            var currentType = e.target.getAttribute('data-current-type');
            toggleContactType(contactId, currentType);
        }
    });
}

function showMainApp(userData) {
    document.getElementById('login-screen').style.display = 'none';
    document.getElementById('main-app').style.display = 'block';
    document.getElementById('user-name').textContent = userData.firstname + ' ' + userData.lastname;

    if (userData.role === 'Admin') {
        document.getElementById('users-nav').style.display = 'block';
        document.getElementById('new-user-nav').style.display = 'block';
    } else {
        document.getElementById('users-nav').style.display = 'none';
        document.getElementById('new-user-nav').style.display = 'none';
    }

    loadPage('dashboard');
}

function showLoginScreen() {
    document.getElementById('login-screen').style.display = 'flex';
    document.getElementById('main-app').style.display = 'none';
    document.getElementById('login-form').reset();
    document.getElementById('login-error').innerHTML = '';
}

function loadPage(page) {
    var contentDiv = document.getElementById('content');
    var url = '';
    
    switch (page) {
        case 'dashboard':
            url = 'api/contacts.php?action=list';
            break;
        case 'new-contact':
            url = 'api/contacts.php?action=form';
            break;
        case 'users':
            url = 'api/users.php?action=list';
            break;
        case 'new-user':
            url = 'api/users.php?action=form';
            break;
        default:
            url = 'api/contacts.php?action=list';
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            contentDiv.innerHTML = xhr.responseText;
            setupForms();
        }
    };
    xhr.send();
}

function loadContacts(filter) {
    var contentDiv = document.getElementById('content');
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'api/contacts.php?action=list&filter=' + filter, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            contentDiv.innerHTML = xhr.responseText;
            setupForms();
        }
    };
    xhr.send();
}

function viewContact(contactId) {
    var contentDiv = document.getElementById('content');
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'api/contacts.php?action=view&id=' + contactId, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            contentDiv.innerHTML = xhr.responseText;
            setupForms();
        }
    };
    xhr.send();
}

function assignToMe(contactId) {
    var messageDiv = document.getElementById('contact-action-message');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/contacts.php?action=assign', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            messageDiv.innerHTML = xhr.responseText;
            // Reload contact view after short delay
            setTimeout(function() {
                viewContact(contactId);
            }, 1000);
        }
    };
    xhr.send('contact_id=' + contactId);
}

function toggleContactType(contactId, currentType) {
    var messageDiv = document.getElementById('contact-action-message');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/contacts.php?action=toggle_type', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            messageDiv.innerHTML = xhr.responseText;
            // Reload contact view after short delay
            setTimeout(function() {
                viewContact(contactId);
            }, 1000);
        }
    };
    xhr.send('contact_id=' + contactId + '&current_type=' + encodeURIComponent(currentType));
}

function setupForms() {
    var addUserForm = document.getElementById('add-user-form');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(addUserForm);
            var messageDiv = document.getElementById('user-form-message');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/users.php?action=add', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    messageDiv.innerHTML = xhr.responseText;
                    if (xhr.responseText.indexOf('success') !== -1) {
                        addUserForm.reset();
                    }
                }
            };

            var params = new URLSearchParams();
            for (var pair of formData.entries()) {
                params.append(pair[0], pair[1]);
            }
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(params.toString());
        });
    }

    var addContactForm = document.getElementById('add-contact-form');
    if (addContactForm) {
        addContactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(addContactForm);
            var messageDiv = document.getElementById('contact-form-message');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/contacts.php?action=add', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    messageDiv.innerHTML = xhr.responseText;
                    if (xhr.responseText.indexOf('success') !== -1) {
                        addContactForm.reset();
                    }
                }
            };

            var params = new URLSearchParams();
            for (var pair of formData.entries()) {
                params.append(pair[0], pair[1]);
            }
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(params.toString());
        });
    }

    var addNoteForm = document.getElementById('add-note-form');
    if (addNoteForm) {
        addNoteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var contactId = addNoteForm.getAttribute('data-contact-id');
            var comment = document.getElementById('note-comment').value;
            var messageDiv = document.getElementById('note-form-message');
            var notesList = document.getElementById('notes-list');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/notes.php?action=add', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    messageDiv.innerHTML = xhr.responseText;
                    if (xhr.responseText.indexOf('success') !== -1) {
                        document.getElementById('note-comment').value = '';
                        // Reload notes list
                        loadNotes(contactId);
                    }
                }
            };
            xhr.send('contact_id=' + contactId + '&comment=' + encodeURIComponent(comment));
        });
    }
}

function loadNotes(contactId) {
    var notesList = document.getElementById('notes-list');
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'api/notes.php?action=list&contact_id=' + contactId, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            notesList.innerHTML = xhr.responseText;
        }
    };
    xhr.send();
}

