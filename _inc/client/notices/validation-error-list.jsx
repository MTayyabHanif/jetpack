/**
 * External dependencies
 */
const PropTypes = require( 'prop-types' );
let React = require( 'react' ),
	map = require( 'lodash/map' );

module.exports = class extends React.Component {
	static displayName = 'ValidationErrorList';

	static propTypes = {
		messages: PropTypes.array.isRequired
	};

	render() {
		return (
			<div>
				<p>
					{ this.translate(
						'Please correct the issue below and try again.',
						'Please correct the issues listed below and try again.',
						{
							count: this.props.messages.length
						}
					) }
				</p>
				<ul>
					{ map( this.props.messages, function( message, index ) {
						return ( <li key={ index }>{ message }</li> );
					} ) }
				</ul>
			</div>
		);
	}
};
