# 💎 Elegance Jewellery Website

A simple jewellery website with basic e-commerce functionality.

---

## ✨ Key Features & Benefits

* **Product Catalog:** Browse a variety of jewellery items.  
* **Basic Ecommerce Functions:** Add to cart (implementation details not provided).  
* **Admin Panel:** Manage products, categories, customers, and orders (PHP-based).  
* **Responsive Design:** Uses CSS media queries (`mobile.css`) for mobile responsiveness.  
* **About Us Page:** Provides information about the website.  

---

## 📸 Project Preview

Here are `Some` visual overview of the **Elegance Jewellery Website** interface and design:

<div align="center">
  <img src="Project Images/1.png" alt="Home Page Preview" width="800px"><br/>
  <img src="Project Images/2.png" alt="Product Page Preview" width="800px"><br/>
  <img src="Project Images/3.png" alt="Cart Page Preview" width="800px"><br/>
  <img src="Project Images/4.png" alt="Admin Panel Preview" width="800px"><br/>
  <img src="Project Images/5.png" alt="Responsive Mobile Preview" width="800px">
</div>

---

## ⚙️ Prerequisites & Dependencies

* **Web Server:** Apache, Nginx, or similar.  
* **PHP:** Version 7.0 or higher (required for admin panel functionality).  
* **JavaScript:** Enabled in the browser.  
* **Database:** MySQL (required for the admin panel, details on setup not included).  
* **Font Awesome:** For icons (linked via CDN).  
* **Google Fonts (Poppins):** For typography (linked via CDN).  

---

## 🚀 Installation & Setup Instructions

1. **Clone the repository:**
    ```bash
    git clone https://github.com/Omcodes23/Elegance-Jewellery-Website.git
    ```

2. **Set up your web server:**
    * Copy the project files to your web server's document root (e.g., `/var/www/html/elegance-jewellery`).

3. **Configure the database (if applicable):**
    * Create a MySQL database.  
    * Update database credentials in the relevant PHP files (e.g., `admin/includes/functions.php`).

4. **Import the database schema (if applicable):**
    * Create necessary tables for products, categories, customers, and orders.

5. **Access the website:**
    * Open your browser and navigate to `http://localhost/elegance-jewellery`.

6. **Admin Panel Access:**
    * Visit `http://localhost/elegance-jewellery/admin-login.php`.  
    * Default admin credentials are not provided — you may need to create an admin user directly in the database.

---

## 🧩 Usage Examples & API Documentation

The JavaScript file `assets/js/buy-now.js` handles the “Buy Now” button events.  
Complete implementation for **Add to Cart** and **Checkout** features is pending.  

This project primarily uses **front-end functionality** with **PHP for admin operations** — no explicit REST API is implemented.

---

## ⚙️ Configuration Options

* **CSS Styling:** Modify `assets/css/style.css` and `assets/css/mobile.css`.  
* **PHP Configuration:** Adjust server-side settings via your `php.ini` file.  
* **Database Configuration:** Update connection details within PHP files.

---

## 🤝 Contributing Guidelines

1. Fork the repository.  
2. Create a new branch for your feature or fix.  
3. Commit your changes with descriptive messages.  
4. Submit a pull request to the main branch.

---

