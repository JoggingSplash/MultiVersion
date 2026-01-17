import base64

# oldest versions like 1.12 had hardcoded nbt
# we will create the file.nbt to create the cache

file = str(input("File name: \n"))
data_base64 = str(input("Hardcoded NBT: "))

data_base64 = data_base64.strip()
nbt_bytes = base64.b64decode(data_base64)

with open(file, "wb") as f:
    f.write(nbt_bytes)

print("File " + file + " created")
