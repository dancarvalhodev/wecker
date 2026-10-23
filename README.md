# Wecker
**A simple and powerful interface inspired by Portainer to control your containers visually.**

![img.png](docs/img2.png)
![img.png](docs/img.png)

Note: Requires Docker Desktop on Windows/macOS.

# Setup Env
1. Install docker.
    1. On windows you need to activate WSL2 and enable "Expose daemon on tcp://localhost:2375 without TLS" option.
2. Install [just](https://just.systems) (e.g. `sudo apt install just`, `brew install just` or `winget install Casey.Just`).
3. Run `just install` (it creates the database and asks for the admin's name, email and password).

# Pending to Fix
- Implement tests.
- Implement BlockUI.
- Improve Documentation.

# Future Implements
- Health Status.
- Docker installation info.
- Web console to manage containers.
- Log Operations (Start, Stop, Log).
- Create containers.
- Real time docker logs.
- Improve UI and UX.