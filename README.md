# Create Custom Chatbot Avatar WordPress Plugin

This project provides a web application that allows Christine's clients to generate a customized WordPress AI chatbot avatar plugin for their websites. Each client can personalize the chatbot plugin by inputting their own **Knowledge Base**, **Predefined Questions**, **Avatar Selection**, and **Language Preference**. The web application then generates a `chatbot.php` file tailored to the client's needs, which can be deployed directly into their WordPress site.

## Features

- **Knowledge Base Customization**: Clients can input specific knowledge related to their business or company for chatbot responses.
- **Predefined Questions**: Allows input of frequently asked questions for quick and automated responses.
- **Avatar Selection**: Clients can choose from a set of predefined avatars or upload their own image for the chatbot.
- **Language Preference**: Supports English and Canadian French. The chatbot's prompts and text-to-speech features will adapt based on the specified language in the shortcode.
- **Custom Plugin Generation**: Automatically creates a customized `chatbot.php` plugin for WordPress.
- **Shortcode-Based Deployment**: Clients can manually specify the language using a shortcode and add the chatbot to specific pages.

---

## Getting Started

### Prerequisites

- PHP installed on your system (version 7.4 or higher recommended).
- Basic knowledge of WordPress plugins and PHP.
- A local or hosted web server (e.g., Apache, Nginx, or PHP's built-in server).

### Installation

1. Clone this repository to your local system:
   ```bash
   git clone https://github.com/Neilsmahajan/create-custom-chatbot-avatar-wordpress-plugin.git
   cd create-custom-chatbot-avatar-wordpress-plugin
   ```
2. Start the built-in PHP server:
   ``` bash
   php -S localhost:8000
   ```
3. Open the application in your browser:
   http://localhost:8000/frontend/index.html
---

## Folder Structure

- **frontend/**: Contains the HTML and JavaScript for the user interface of the web app.
- **backend/**: Contains the PHP scripts to handle form submissions and generate the custom WordPress plugin.
- **generated-plugins/**: Stores the generated chatbot.php files for each client.
- **uploads/**: Stores the avatar images uploaded by clients.

---

## Usage

1. Open the application in your browser.
2. Fill in the fields for:
   - **Knowledge Base:** Enter the specific details of your business or organization.
   - **Predefined Questions:** Provide frequently asked questions for the chatbot to address.
   - **Avatar Selection:** Choose a predefined avatar or upload your own image.
3. Click the Submit button.
4. Download the generated `chatbot.php` file from the provided link.
5. Place the `chatbot.php` file in the `/wp-content/plugins/chatbot` folder of your WordPress website.
6. Place your avatar image, `vendor` folder, and `gcp-text-to-speech-demo-service-account.json` file from the generated-plugins folder into the same folder as your `chatbot.php` file.
7. Activate the plugin in the WordPress admin dashboard.

---

## Adding the Chatbot to WordPress Pages

### Shortcode Example
- To add the chatbot to a WordPress page in **English**, use:
   ```
   [chatbot_avatar language="en-US"]
   ```
- To add the chatbot to a WordPress page in **Canadian French**, use:
   ```
   [chatbot_avatar language="fr-CA"]
   ```

---

## Example Workflow

### For a Real Estate Client:

1. **Knowledge Base:**
   - **English:**
      ```
      You are a helpful assistant for a real estate business called Neil's Test Real Estate. Currently Neil's Test Real Estate operates in Scottsdale and has four homes, two of which are for sale.
      ```
   - **Canadian French:**
      ```
      Vous êtes un assistant de confiance pour une entreprise immobilière appelée Neil's Test Real Estate. Actuellement, Neil's Test Real Estate opère à Scottsdale et a quatre maisons, dont deux sont à vendre.
      ```
2. **Predefined Questions:**
   - **English:**
      ```
      - What are my Hours of Operation? 9 AM to 5 PM Monday through Thursday, 9 AM to 3 PM Friday.
      - What are the current real estate trends in the city? The market is hot, with prices increasing by 10% over the past year.
      - How can I find the perfect home for my family? I can search for homes in my price range, and I can also search for homes by location.
      - What is the process for selling my property? First, I need to find a real estate agent, then we will discuss the price of the home, and then we will list the home on the market.
      ```
   - **Canadian French:**
      ```
      - Quels sont mes horaires d'ouverture? 9h à 17h du lundi au jeudi, 9h à 15h le vendredi.
      - Quels sont les tendances actuelles de l'immobilier dans la ville? Le marché est chaud, avec des prix en hausse de 10% au cours de l'année dernière.
      - Comment puis-je trouver la maison idéale pour ma famille? Je peux rechercher des maisons dans ma gamme de prix, et je peux également rechercher des maisons par emplacement.
      - Quel est le processus pour vendre ma propriété? Tout d'abord, je dois trouver un agent immobilier, puis nous discuterons du prix de la maison, et ensuite nous listerons la maison sur le marché.
      ```
3. **Avatar Selection:** Upload a custom image of a professional real estate agent.
4. **Language Preference:** Use the appropriate shortcode (`en-US` for English or `fr-CA` for French).

The generated plugin will contain these customizations and provide a tailored experience on their WordPress site.

---

## Technical Details

- **Frontend:** HTML, CSS, and JavaScript are used for the user interface, with a focus on simplicity and user-friendliness.
- **Backend:** PHP scripts handle data processing, avatar uploads, and dynamic plugin generation.
- **WordPress Integration:** The generated chatbot.php file uses the OpenAI ChatGPT API and Google Cloud Text-to-Speech API for chatbot and voice interactions.

### Template Placeholders in chatbot.php

The backend replaces these placeholders in the template with the client's input:
- {{KNOWLEDGE_BASE}}: The knowledge base provided by the client.
- {{PREDEFINED_QUESTIONS}}: The predefined questions provided by the client.
- {{AVATAR}}: The avatar image provided by the client.

---

## Development Commands

### Git Branch Management
- **Create a New Branch:**
   ``` bash
   git checkout -b feature-name
   ```
- **Push a Branch to Remote:**
   ``` bash
   git push -u origin feature-name
   ```
- **Merge a Branch into Main:**
   ``` bash
   git checkout main
   git merge feature-name
   git push origin main
   ```

### Start Local Development:
   ``` bash
   php -S localhost:8000
   ```

---

## Future Enhancements

- **User Authentication:** Add user accounts for each client to save their preferences for future updates.
- **Dynamic Multi-Language Support:** Detect the website language dynamically and load the chatbot accordingly.
- **Customization Options:** Allow customization of text color, button styles, and chatbot positioning.
- **Improved UI/UX:** Enhance the design with real-time previews and interactivity.

---

## Contact

For questions or feedback, please contact **Neil Mahajan** at neilsmahajan@gmail.com or nsmahaj1@asu.edu.<br/>
Other team members:
- **Pranav Vad:** pvad1@asu.edu
- **Abbhijit Venkatachalam:** avenka97@asu.edu
- **Sai Kolla:** slkolla@asu.edu

---

Thank you for using the **Create Custom Chatbot Avatar WordPress Plugin!** 🎉