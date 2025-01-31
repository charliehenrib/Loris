import React, {Component} from 'react';
import PropTypes from 'prop-types';

import swal from 'sweetalert2';
import Loader from 'Loader';

/**
 * Media Upload Form
 *
 * Fetches data from Loris backend and displays a form allowing
 * to upload a document file
 *
 * @author Shen Wang
 * @version 1.0.0
 */
class DocUploadForm extends Component {
  /**
   * @constructor
   * @param {object} props - React Component properties
   */
  constructor(props) {
    super(props);

    this.state = {
      data: {},
      error: false,
      formData: {},
      uploadResult: null,
      errorMessage: null,
      isLoaded: false,
    };

    this.setFormData = this.setFormData.bind(this);
    this.uploadFile = this.uploadFile.bind(this);
    this.fetchData = this.fetchData.bind(this);
  }

  /**
   * Called by React when the component has been rendered on the page.
   */
  componentDidMount() {
    this.fetchData()
      .then(() => this.setState({isLoaded: true}));
  }

  /**
   * Called by React when props are passed to the Component instance
   *
   * @param {object} nextProps
   */
  componentWillReceiveProps(nextProps) {
    // Any time props.category changes, update state.
    if (nextProps.category) {
      this.fetchData();
    }
  }

  /**
   * Fetch data
   *
   * @return {Promise}
   */
  fetchData() {
    return fetch(this.props.dataURL, {credentials: 'same-origin'})
      .then((resp) => resp.json())
      .then((data) => this.setState({data: data, isLoaded: true}))
      .catch((error) => {
        this.setState({error: true});
        console.error(error);
      });
  }

  /**
   * Renders the React component.
   *
   * @return {JSX} - React markup for the component
   */
  render() {
    // Data loading error
    if (this.state.error) {
       return <h3>An error occured while loading the page.</h3>;
     }
    // Waiting for data to load
    if (!this.state.isLoaded) {
      return (<Loader/>);
    }
    return (
      <div className="row">
        <div className="col-md-8 col-lg-7">
          <FormElement
            name="docUpload"
            fileUpload={true}
            onSubmit={this.uploadFile}
            method="POST"
          >
            <h3>Upload a file</h3><br/>
            <SelectElement
              name="category"
              label="Category"
              options={this.state.data.fieldOptions.fileCategories}
              onUserInput={this.setFormData}
              hasError={false}
              required={true}
              value={this.state.formData.category}
            />
            <SearchableDropdown
              name="forSite"
              label="Site"
              placeHolder="Search for site"
              options={this.state.data.fieldOptions.sites}
              strictSearch={true}
              onUserInput={this.setFormData}
              required={true}
              value={this.state.formData.forSite}
            />
            <SelectElement
              name="instrument"
              label="Instrument"
              options={this.state.data.fieldOptions.instruments}
              onUserInput={this.setFormData}
              value={this.state.formData.instrument}
            />
            <TextboxElement
              name="pscid"
              label="PSCID"
              onUserInput={this.setFormData}
              value={this.state.formData.pscid}
            />
            <TextboxElement
              name="visitLabel"
              label="Visit Label"
              onUserInput={this.setFormData}
              value={this.state.formData.visitLabel}
            />
            <TextboxElement
              name="version"
              label="Version"
              onUserInput={this.setFormData}
              value={this.state.formData.version}
            />
            <TextareaElement
              name="comments"
              label="Comments"
              onUserInput={this.setFormData}
              value={this.state.formData.comments}
            />
            <FileElement
              name="file"
              id="docUploadEl"
              onUserInput={this.setFormData}
              label="File to upload"
              required={true}
              value={this.state.formData.file}
            />
            <ButtonElement label="Upload File"/>
          </FormElement>
        </div>
      </div>
    );
  }

  /**
   * *******************************************************************************
   *                      ******     Helper methods     *******
   ********************************************************************************
   */

  /**
   * Upload file
   */
  uploadFile() {
    // Set form data and upload the media file
    let formData = this.state.formData;
    let formObject = new FormData();
    for (let key in formData) {
      if (formData[key] !== '') {
        formObject.append(key, formData[key]);
      }
    }

    fetch(this.props.action, {
      method: 'POST',
      cache: 'no-cache',
      credentials: 'same-origin',
      body: formObject,
    })
    .then((resp) => {
      console.error(resp);
      if (resp.ok) {
        swal.fire('Upload Successful!', '', 'success').then((result) => {
          if (result.value) {
            this.setState({formData: {}});
            this.props.refreshPage();
          }
        });
      } else {
        resp.json().then((data) => {
          swal.fire('Could not upload file', data.error, 'error');
        }).catch((error) => {
          console.error(error);
          swal.fire(
            'Unknown Error',
            'Please report the issue or contact your administrator',
            'error'
          );
        });
      }
    });
  }

  /**
   * Set the form data based on state values of child elements/componenets
   *
   * @param {string} formElement - name of the selected element
   * @param {string} value - selected value for corresponding form element
   */
  setFormData(formElement, value) {
    let formData = this.state.formData;
    formData[formElement] = value;

    this.setState({formData: formData});
  }
}

DocUploadForm.propTypes = {
  dataURL: PropTypes.string.isRequired,
  action: PropTypes.string.isRequired,
  refreshPage: PropTypes.func.isRequired,
  category: PropTypes.string,
};

export default DocUploadForm;
