# Upgrade to 0.3

## BC Break: Fixed MongoDB storage usage

Before v0.3 the storage name associated to a class wasn't used when the storage is `MongoDbStorage`.
In order to be consistent with other storage drivers, the `storageName` is now used for the collection name when storing and data.
To get the same behavior as in older versions, pass the collection name given in the constructor arguments as storage name.
