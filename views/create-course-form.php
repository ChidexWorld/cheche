<form action="../api/create-course.php" method="POST" style="max-width: 600px;">
    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label for="title" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Course Title:</label>
        <input type="text" name="title" id="title" required maxlength="255"
               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
               placeholder="Enter a compelling course title">
    </div>

    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label for="description" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Course Description:</label>
        <textarea name="description" id="description" rows="6" required
                  style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; resize: vertical;"
                  placeholder="Describe what students will learn in this course, the target audience, and any prerequisites"></textarea>
    </div>

    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label for="category" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Category:</label>
        <select name="category" id="category"
                style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
            <option value="">Select a category (optional)</option>
            <option value="Programming">Programming</option>
            <option value="Web Development">Web Development</option>
            <option value="Mobile Development">Mobile Development</option>
            <option value="Data Science">Data Science</option>
            <option value="Machine Learning">Machine Learning</option>
            <option value="Design">Design</option>
            <option value="Marketing">Marketing</option>
            <option value="Business">Business</option>
            <option value="Photography">Photography</option>
            <option value="Music">Music</option>
            <option value="Language Learning">Language Learning</option>
            <option value="Health & Fitness">Health & Fitness</option>
            <option value="Personal Development">Personal Development</option>
            <option value="Other">Other</option>
        </select>
    </div>

    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label for="level" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Difficulty Level:</label>
        <select name="level" id="level"
                style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
            <option value="">Select difficulty level (optional)</option>
            <option value="Beginner">Beginner</option>
            <option value="Intermediate">Intermediate</option>
            <option value="Advanced">Advanced</option>
            <option value="All Levels">All Levels</option>
        </select>
    </div>

    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label for="price" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Price (USD):</label>
        <input type="number" name="price" id="price" min="0" step="0.01" value="0"
               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
               placeholder="0.00">
        <small style="color: #888; display: block; margin-top: 0.5rem;">
            Set to 0 for a free course. You can change this later.
        </small>
    </div>

    <div class="form-group" style="margin-bottom: 1.5rem;">
        <button type="submit" class="btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem;">
            Create Course
        </button>
    </div>
</form>

<div style="background: #f8f9fa; padding: 1.5rem; border-radius: 4px; margin-top: 2rem;">
    <h4 style="margin-top: 0;">Course Creation Tips:</h4>
    <ul style="margin-bottom: 0;">
        <li>Choose a clear, specific title that accurately describes your course</li>
        <li>Write a detailed description that explains the learning objectives</li>
        <li>Select the appropriate category and difficulty level to help students find your course</li>
        <li>Start with a free course to build your reputation, then create paid courses</li>
        <li>After creating your course, add videos from the "Upload Video" tab</li>
    </ul>
</div>