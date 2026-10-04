# Ivo - Testimonials & Reviews (WordPress plugin)

Custom WordPress plugin adding a "Testimonial" content type with a star rating, a [testimonials] shortcode for a responsive grid layout, and a REST API endpoint for headless/JS use.

## Features

- Custom post type `testimonial` with author name, company/role, and a 1-5 star rating (meta box)
- `[testimonials count="6" columns="3"]` shortcode renders a responsive card grid
- REST endpoint: `GET /wp-json/ivo/v1/testimonials`

## Live example

Rendered output below (sample content for a fictional bakery):

<img width="700" alt="Testimonials grid with 3 fictional bakery reviews rendered on the frontend" src="screenshot.png" />
