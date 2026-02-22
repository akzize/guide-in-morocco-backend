# Guide in Morocco - Backend API

This is the backend API for the "Guide in Morocco" application, built with Laravel. It provides endpoints for users, guides, tours, bookings, and reviews.

## API Documentation

Base URL: `/api`

### Authentication
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST   | `/register` | Register a new user | No |
| POST   | `/login`    | Login a user | No |
| POST   | `/logout`   | Logout current user | Yes (Sanctum) |
| GET    | `/user`     | Get authenticated user profile | Yes (Sanctum) |

### Public Routes
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET    | `/lookups` | Get application lookup data (cities, languages, etc.) |
| GET    | `/tours`   | List all available tours |
| GET    | `/tours/{tour}` | Get details of a specific tour |
| GET    | `/guides`  | List all guides |
| GET    | `/guides/{guide}` | Get details of a specific guide |
| GET    | `/reviews/tours/{tour}` | Get all reviews for a specific tour |
| GET    | `/reviews/guides/{guide}` | Get all reviews for a specific guide |

### Protected Routes (Requires Bearer Token)

#### Tours (Guide Only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST   | `/tours` | Create a new tour |
| PUT/PATCH | `/tours/{tour}` | Update an existing tour |
| DELETE | `/tours/{tour}` | Delete a tour |

#### Bookings
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET    | `/bookings` | List a user's bookings |
| POST   | `/bookings` | Create a new booking |
| GET    | `/bookings/{booking}` | Get booking details |
| PUT/PATCH | `/bookings/{booking}` | Update a booking |
| DELETE | `/bookings/{booking}` | Cancel a booking |

#### Reviews
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST   | `/reviews` | Create a new review |
| PUT/PATCH | `/reviews/{review}` | Update an existing review |
| DELETE | `/reviews/{review}` | Delete a review |

#### Admin Routes
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST   | `/admin/guides/{guide}/activate` | Activate a guide account | Yes (Admin only) |

## Standard Responses
- `200 OK`: Request successful.
- `201 Created`: Resource successfully created.
- `401 Unauthorized`: Authentication failed or missing token.
- `403 Forbidden`: User does not have necessary permissions.
- `404 Not Found`: Resource not found.
- `422 Unprocessable Entity`: Validation errors.
