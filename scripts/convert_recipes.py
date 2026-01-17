import json
import os

# Convert old format of BedrockData recipes to latest format

MAIN_DIR = "../resources/v419/"

INPUT_FILE = MAIN_DIR + "recipes.json"
OUTPUT_DIR = MAIN_DIR + "recipes"

os.makedirs(OUTPUT_DIR, exist_ok=True)

with open(INPUT_FILE, "r", encoding="utf-8") as f:
    data = json.load(f)

for key, value in data.items():
    output_path = os.path.join(OUTPUT_DIR, f"{key}.json")

    with open(output_path, "w", encoding="utf-8") as out:
        json.dump(value, out, indent=2, ensure_ascii=False)

    print(f"Successful made: {output_path}")

print("Finished")
