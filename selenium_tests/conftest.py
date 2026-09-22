import os
import pytest
from selenium import webdriver
from selenium.webdriver.chrome.options import Options as ChromeOptions
from selenium.webdriver.edge.options import Options as EdgeOptions

BASE_URL = os.getenv("APP_URL", "http://localhost:8000")

@pytest.fixture(scope="function")
def driver():
    """Fixture to initialize and tear down headless Chrome/Edge WebDriver."""
    driver_instance = None
    try:
        chrome_options = ChromeOptions()
        chrome_options.add_argument("--headless=new")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1920,1080")
        chrome_options.add_argument("--ignore-certificate-errors")
        driver_instance = webdriver.Chrome(options=chrome_options)
    except Exception as e:
        print(f"Chrome initialization failed: {e}. Falling back to Microsoft Edge...")
        edge_options = EdgeOptions()
        edge_options.add_argument("--headless=new")
        edge_options.add_argument("--disable-gpu")
        edge_options.add_argument("--window-size=1920,1080")
        driver_instance = webdriver.Edge(options=edge_options)

    driver_instance.implicitly_wait(10)
    yield driver_instance
    driver_instance.quit()
