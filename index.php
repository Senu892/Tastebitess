<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-white py-4">
        <div class="max-w-7lg mx-auto px-4 flex justify-between items-center">
            <img src="logo.png" alt="TasteBites" class="h-8">
            <div class="flex space-x-8 items-center">
                <a href="#" class="text-black">Home</a>
                <a href="#" class="text-black">Snacks</a>
                <a href="#" class="text-black">Subscription</a>
                <a href="#" class="text-black">About Us</a>
                <button class="bg-[#FFDAC1] px-6 py-1 rounded-full">Senudi</button>
                <button class="text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="max-w-7xl mx-auto px-4 py-16 grid grid-cols-2 gap-8 items-center">
        <div>
            <h1 class="text-4xl font-bold mb-4">
                A World of<br>
                Flavors at <span class="text-[#DC143C]">Your Doorstep!</span>
            </h1>
            <p class="hero-text-color mb-8">
                Discover global flavors with our curated snack boxes. From familiar treats to rare rarities, TasteBites has it all. Explore tastes from across the globe – all perfectly packaged and delivered to your door!
            </p>
            <button class="bg-[#DC143C] text-white px-8 py-3 rounded-full">
                Order Now
            </button>
        </div>
        <div>
            <img src="hero-snackbox.png" alt="Snack Box Collection" class="w-full">
        </div>
    </section>

    <!-- Customized Snack Boxes -->
    <section class="max-w-7xl mx-auto px-4 py-16">
        <div class="text-center mb-12">
            <h2 class="text-[#DC143C] text-2xl font-bold">Customized Snack Boxes</h2>
            <h3 class="text-3xl font-bold mt-2">Find Your Perfect Box</h3>
        </div>
        
        <div id="product-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Product cards will be inserted dynamically here -->
        </div>
    </main>

    <script>
        // Fetch products from the backend
        async function fetchProducts() {
        try {
            const response = await fetch('http://localhost/tut3/Routes.php/get-all-products');

            console.log('response')
            console.log(response)
            const data = await response.json();

            console.log('data')
            console.log(data)


            if (data.status === 'success') {
                const products = data.data;
                console.log('products')
                console.log(products);
                displayProducts(products);
            } else {
                console.error('Error fetching products:', data.message);
            }
        } catch (error) {
            console.error('Error fetching products:', error);
        }
    }



        // Function to display products in the DOM
        function displayProducts(products) {
            const productContainer = document.getElementById('product-container');
            productContainer.innerHTML = ''; // Clear existing content

            products.forEach(product => {
                const productCard = `
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <img src="${product.product_image}" alt="${product.id}" class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h3 class="text-lg font-bold text-gray-800">${product.product_name}</h3>
                            <p class="text-gray-600 mt-2">$${product.product_price}</p>
                            <p class="text-gray-800 font-bold mt-4">${product.product_quantity}</p>
                        </div>
                    </div>
                `;
                productContainer.innerHTML += productCard;
            });
        }

        // Load products on page load
        fetchProducts();
    </script>
</body>
</html>