<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                Sign in to your account
            </h2>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <form id="loginForm" class="space-y-6">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium leading-6 text-gray-900">
                            Email address
                        </label>
                        <div class="mt-2">
                            <input 
                                id="email" 
                                name="email" 
                                type="email" 
                                required 
                                class="block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium leading-6 text-gray-900">
                            Password
                        </label>
                        <div class="mt-2">
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                required 
                                class="block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            >
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input 
                                id="remember-me" 
                                name="remember-me" 
                                type="checkbox" 
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                            >
                            <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                                Remember me
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">
                                Forgot your password?
                            </a>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button 
                            type="submit" 
                            class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        >
                            Sign in
                        </button>
                    </div>

                    <!-- Sign Up Link -->
                    <div class="text-center text-sm">
                        <span class="text-gray-500">Don't have an account?</span>
                        <a href="signup.php" class="ml-1 font-medium text-indigo-600 hover:text-indigo-500">
                            Sign up
                        </a>
                    </div>
                </form>

                <!-- Message Display -->
                <div id="message" class="mt-4 text-center hidden">
                    <p class="text-sm"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const messageDiv = document.getElementById('message');

            // Basic validation
            if (!email || !password) {
                messageDiv.classList.remove('hidden');
                messageDiv.innerHTML = '<p class="text-red-600">Please fill in all fields</p>';
                return;
            }

            try {
                const response = await fetch('api.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();
                messageDiv.classList.remove('hidden');
                
                // Update the success handler in your login form submission
                    if (data.status === 'success') {
                        // Show success message
                        messageDiv.innerHTML = `<p class="text-green-600">${data.message}</p>`;
                        
                        // Handle "Remember me" option
                        if (document.getElementById('remember-me').checked) {
                            localStorage.setItem('rememberMe', 'true');
                            localStorage.setItem('user', JSON.stringify(data.data));
                        }
                        
                        // Redirect after successful login
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 1500);
                    } else {
                    // Show error message
                    messageDiv.innerHTML = `<p class="text-red-600">${data.message}</p>`;
                }
            } catch (error) {
                console.error('Error:', error);
                messageDiv.classList.remove('hidden');
                messageDiv.innerHTML = '<p class="text-red-600">An error occurred. Please try again later.</p>';
            }
        });

        // Check for remembered user on page load
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('rememberMe') === 'true') {
                const user = JSON.parse(localStorage.getItem('user'));
                if (user && user.email) {
                    document.getElementById('email').value = user.email;
                    document.getElementById('remember-me').checked = true;
                }
            }
        });
    </script>
</body>
</html>